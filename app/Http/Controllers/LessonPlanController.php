<?php

namespace App\Http\Controllers;

use App\Mail\LessonPlanUploaded;
use App\Models\LessonPlan;
use App\Http\Requests\StoreLessonPlanRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

/**
 * Handles CRUD operations for lesson plans.
 *
 * Key behaviors:
 * - Upload creates a new lesson plan (version 1) with a canonical filename.
 * - "New Version" creates a child version linked to a parent plan.
 * - Files are renamed on disk to the canonical naming format:
 *   {ClassName}_Day{N}_{AuthorName}_{YYYYMMDD_HHMMSS}UTC.{ext}
 * - SHA-256 file hash is computed on upload for duplicate content detection.
 * - Upload confirmation emails are sent asynchronously (failures logged, not blocking).
 *
 * DESIGN DECISION — Author locked to logged-in user:
 *   The author is always set to the currently authenticated user (Auth::id()).
 *   Previously, the form allowed selecting any registered user as the author,
 *   but this was changed to prevent impersonation and simplify the upload flow.
 *
 * DESIGN DECISION — Confirmation email recipient:
 *   The upload confirmation email is always sent to the person performing the
 *   upload (Auth::user()), not the selected author. This is intentional:
 *   the uploader needs confirmation that their action succeeded. The selected
 *   author is not necessarily expecting a notification at that moment.
 */
class LessonPlanController extends Controller
{
    /**
     * Allowed class names for the upload dropdown.
     *
     * To add new subjects, simply append them to this array.
     * The StoreLessonPlanRequest validation references this constant
     * to enforce the allowed values server-side.
     */
    public const CLASS_NAMES = ['English', 'Mathematics', 'Science'];

    /**
     * My Plans: list all plans authored by the current user.
     *
     * Shows all versions (not just latest) authored by this user,
     * sorted most-recent first, paginated at 25 per page.
     */
    public function myPlans(Request $request)
    {
        $plans = LessonPlan::where('author_id', Auth::id())
            ->with('author')
            ->orderBy('updated_at', 'desc')
            ->paginate(25);

        return view('lesson-plans.my-plans', compact('plans'));
    }

    /**
     * Show the upload form for a brand-new lesson plan.
     *
     * Passes dropdown data to the view:
     * - $classNames: restricted list of allowed class names
     * - $lessonNumbers: 1 through 20
     * - $authors: all registered users (name keyed by id), alphabetical
     */
    public function create()
    {
        $classNames    = self::CLASS_NAMES;
        $lessonNumbers = range(1, 20);

        return view('lesson-plans.create', compact('classNames', 'lessonNumbers'));
    }

    /**
     * Store a brand-new lesson plan (version 1, no parent).
     *
     * Flow:
     * 1. Validate form data (class_name, lesson_day, author_id, file).
     * 2. Look up the selected author for canonical name generation.
     * 3. Generate canonical name with UTC timestamp.
     * 4. Check for duplicate name (same class/day/author within same second).
     * 5. Rename the uploaded file to the canonical name + original extension.
     * 6. Compute SHA-256 hash for future duplicate content detection.
     * 7. Create the LessonPlan record (version 1, no parent/original).
     * 8. Send confirmation email to the uploader (not the selected author).
     * 9. Redirect to the plan's detail page with a success dialog.
     */
    public function store(StoreLessonPlanRequest $request)
    {
        $data = $request->validated();

        // Author is always the logged-in user (see class-level design note)
        $author = Auth::user();

        // Generate canonical name using the author's display name (email)
        $canonicalName = LessonPlan::generateCanonicalName(
            $data['class_name'],
            $data['lesson_day'],
            $author->name
        );

        // Guard: reject if this exact canonical name already exists.
        // This can happen if the same class/day/author uploads twice in
        // the same second (the timestamp is second-resolution).
        if (LessonPlan::where('name', $canonicalName)->exists()) {
            return back()->withInput()->with('error',
                'A lesson plan with the name "' . $canonicalName . '" already exists. '
                . 'This can happen if you upload the same class/day combination within the same second. '
                . 'Please wait a moment and try again.');
        }

        // Handle file upload — rename to canonical name + original extension
        $file      = $request->file('file');
        $extension = $file->getClientOriginalExtension();
        $fileSize  = $file->getSize();
        $diskName  = $canonicalName . '.' . $extension;

        // Compute SHA-256 hash of file contents for duplicate content detection.
        // The DetectDuplicateContent artisan command uses this hash to find
        // and remove files with identical content.
        $fileHash = hash_file('sha256', $file->getRealPath());

        // Store with canonical filename in the public disk under lessons/
        $filePath = $file->storeAs('lessons', $diskName, 'public');

        // Create the lesson plan record — this is version 1 (no parent/original)
        $plan = LessonPlan::create([
            'class_name'     => $data['class_name'],
            'lesson_day'     => $data['lesson_day'],
            'description'    => $data['description'] ?? null,
            'name'           => $canonicalName,
            'author_id'      => $author->id,
            'version_number' => 1,
            'file_path'      => $filePath,
            'file_name'      => $diskName,
            'file_size'      => $fileSize,
            'file_hash'      => $fileHash,
        ]);

        // Send confirmation email to the person who performed the upload
        $this->sendUploadConfirmationEmail($plan, $diskName);

        // Flash data triggers the upload-success modal dialog in layout.blade.php
        return redirect()->route('lesson-plans.show', $plan)
            ->with('upload_success', true)
            ->with('upload_filename', $diskName);
    }

    /**
     * View a single lesson plan with its details, votes, and version history.
     *
     * Public route — no auth required. Anyone can view and download plans.
     * Voting requires authentication (handled in the view with @auth).
     */
    public function show(LessonPlan $lessonPlan)
    {
        $lessonPlan->load(['author', 'votes']);

        // Get all versions in this plan's family (same original_id)
        $versions = $lessonPlan->familyVersions()
            ->with('author')
            ->get();

        // Check if the current user has already voted on this specific version
        $userVote = null;
        if (Auth::check()) {
            $userVote = $lessonPlan->votes()
                ->where('user_id', Auth::id())
                ->first();
        }

        return view('lesson-plans.show', compact('lessonPlan', 'versions', 'userVote'));
    }

    /**
     * Show the form to create a new version of an existing plan.
     *
     * Pre-fills the class name and lesson day from the parent version.
     * The user can change these if needed (e.g., reassigning to a different class).
     */
    public function edit(LessonPlan $lessonPlan)
    {
        $classNames    = self::CLASS_NAMES;
        $lessonNumbers = range(1, 20);

        return view('lesson-plans.edit', compact('lessonPlan', 'classNames', 'lessonNumbers'));
    }

    /**
     * Store a new version derived from an existing plan.
     *
     * Similar to store(), but:
     * - Links to the parent plan via original_id and parent_id.
     * - Auto-increments the version number within the family.
     * - Uses LessonPlan::createNewVersion() for the linkage logic.
     */
    public function update(StoreLessonPlanRequest $request, LessonPlan $lessonPlan)
    {
        $data = $request->validated();

        // Author is always the logged-in user (see class-level design note)
        $author = Auth::user();

        // Generate canonical name for the new version
        $canonicalName = LessonPlan::generateCanonicalName(
            $data['class_name'],
            $data['lesson_day'],
            $author->name
        );

        // Guard: reject duplicate canonical names
        if (LessonPlan::where('name', $canonicalName)->exists()) {
            return back()->withInput()->with('error',
                'A lesson plan with the name "' . $canonicalName . '" already exists. '
                . 'Please wait a moment and try again.');
        }

        // Handle file upload — rename to canonical name + original extension
        $file      = $request->file('file');
        $extension = $file->getClientOriginalExtension();
        $fileSize  = $file->getSize();
        $diskName  = $canonicalName . '.' . $extension;

        // Compute SHA-256 hash for duplicate detection
        $fileHash = hash_file('sha256', $file->getRealPath());

        // Store with canonical filename
        $filePath = $file->storeAs('lessons', $diskName, 'public');

        // Create the new version, linked to the parent plan's family
        $newVersion = $lessonPlan->createNewVersion([
            'class_name'    => $data['class_name'],
            'lesson_day'    => $data['lesson_day'],
            'description'   => $data['description'] ?? null,
            'name'          => $canonicalName,
            'author_id'     => $author->id,
            'file_path'     => $filePath,
            'file_name'     => $diskName,
            'file_size'     => $fileSize,
            'file_hash'     => $fileHash,
        ]);

        // Send confirmation email to the uploader
        $this->sendUploadConfirmationEmail($newVersion, $diskName);

        // Flash data triggers the upload-success modal dialog
        return redirect()->route('lesson-plans.show', $newVersion)
            ->with('upload_success', true)
            ->with('upload_filename', $diskName);
    }

    /**
     * Show a document preview page with an embedded viewer.
     *
     * Public route — anyone can preview files. Uses Google Docs Viewer
     * to render .doc/.docx files in the browser without requiring any
     * server-side conversion. The preview page includes a download button
     * for users who want to save the file locally.
     *
     * If the plan has no file attached, redirects to the detail page.
     */
    public function preview(LessonPlan $lessonPlan)
    {
        if (!$lessonPlan->file_path) {
            return redirect()->route('lesson-plans.show', $lessonPlan)
                ->with('error', 'This plan does not have a file attached.');
        }

        $lessonPlan->load('author');

        return view('lesson-plans.preview', compact('lessonPlan'));
    }

    /**
     * Download the file attached to a lesson plan.
     *
     * Public route — anyone can download. The file is served with the
     * canonical filename so the user gets a meaningful file name.
     */
    public function download(LessonPlan $lessonPlan)
    {
        if (!$lessonPlan->file_path || !Storage::disk('public')->exists($lessonPlan->file_path)) {
            return back()->with('error', 'File not found.');
        }

        return Storage::disk('public')->download(
            $lessonPlan->file_path,
            $lessonPlan->file_name ?? basename($lessonPlan->file_path)
        );
    }

    /**
     * Delete a lesson plan (only the author can delete their own).
     *
     * Guards:
     * 1. Only the author who uploaded this version can delete it.
     * 2. A plan cannot be deleted if other versions in its family still
     *    exist. This prevents orphaned records — both direct children
     *    (via parent_id) and family members (via original_id) are checked.
     *    The root plan guard checks for any descendants; non-root plans
     *    check for direct children. Users must delete from the leaves inward.
     *
     * Also cleans up:
     * - The stored file on disk
     * - All votes associated with this version
     */
    public function destroy(LessonPlan $lessonPlan)
    {
        // Guard: only the author can delete
        if ($lessonPlan->author_id !== Auth::id()) {
            abort(403, 'You can only delete your own lesson plans.');
        }

        // Guard: prevent deleting a root plan if ANY descendants still exist
        // (not just direct children — intermediate deletions can null parent_id,
        // so we check original_id which links all family members to the root)
        if ($lessonPlan->is_original) {
            $hasDescendants = LessonPlan::where('original_id', $lessonPlan->id)->exists();
            if ($hasDescendants) {
                return back()->with('error',
                    'This is the original version and other versions are based on it. '
                    . 'Please delete the newer versions first.');
            }
        }

        // Guard: prevent deleting intermediate versions that have direct children
        if (!$lessonPlan->is_original && $lessonPlan->children()->exists()) {
            return back()->with('error',
                'Other versions were created from this one. '
                . 'Please delete those newer versions first.');
        }

        // Delete the file from storage
        if ($lessonPlan->file_path && Storage::disk('public')->exists($lessonPlan->file_path)) {
            Storage::disk('public')->delete($lessonPlan->file_path);
        }

        // Clean up votes before deleting the record (avoids FK constraint issues)
        $lessonPlan->votes()->delete();
        $lessonPlan->delete();

        return redirect()->route('my-plans')
            ->with('success', 'Lesson plan deleted.');
    }

    /**
     * Send an upload confirmation email to the authenticated user (the uploader).
     *
     * Note: this always emails Auth::user(), NOT the selected author_id.
     * See the class-level design note for the rationale.
     *
     * Wrapped in try/catch so a mail failure never blocks the upload itself.
     * Failures are logged to storage/logs/laravel.log.
     */
    private function sendUploadConfirmationEmail(LessonPlan $plan, string $diskName): void
    {
        try {
            $user = Auth::user();
            Mail::to($user->email)->send(new LessonPlanUploaded(
                recipientName:     $user->name,
                canonicalFilename: $diskName,
                className:         $plan->class_name,
                lessonDay:         $plan->lesson_day,
                versionNumber:     $plan->version_number,
                viewUrl:           route('lesson-plans.show', $plan),
            ));
        } catch (\Exception $e) {
            // Log but don't fail — the upload itself was successful
            \Illuminate\Support\Facades\Log::warning(
                'Upload confirmation email failed: ' . $e->getMessage()
            );
        }
    }
}
