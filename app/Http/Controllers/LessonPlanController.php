<?php

namespace App\Http\Controllers;

use App\Mail\LessonPlanUploaded;
use App\Models\LessonPlan;
use App\Http\Requests\StoreLessonPlanRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class LessonPlanController extends Controller
{
    /**
     * My Plans: list all plans authored by the current user.
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
     */
    public function create()
    {
        return view('lesson-plans.create');
    }

    /**
     * Store a brand-new lesson plan (version 1, no parent).
     * The canonical name is auto-generated from class_name, lesson_day,
     * the logged-in user's name, and the current UTC timestamp.
     * The uploaded file is renamed on disk to match the canonical name.
     */
    public function store(StoreLessonPlanRequest $request)
    {
        $data = $request->validated();

        // Generate canonical name
        $authorName = Auth::user()->name;
        $canonicalName = LessonPlan::generateCanonicalName(
            $data['class_name'],
            $data['lesson_day'],
            $authorName
        );

        // Check for duplicate name
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

        // Compute SHA-256 hash of file contents for duplicate content detection
        $fileHash = hash_file('sha256', $file->getRealPath());

        // Store with canonical filename
        $filePath = $file->storeAs('lessons', $diskName, 'public');

        $plan = LessonPlan::create([
            'class_name'     => $data['class_name'],
            'lesson_day'     => $data['lesson_day'],
            'description'    => $data['description'] ?? null,
            'name'           => $canonicalName,
            'author_id'      => Auth::id(),
            'version_number' => 1,
            'file_path'      => $filePath,
            'file_name'      => $diskName,
            'file_size'      => $fileSize,
            'file_hash'      => $fileHash,
        ]);

        // Send confirmation email to the uploader
        $this->sendUploadConfirmationEmail($plan, $diskName);

        return redirect()->route('lesson-plans.show', $plan)
            ->with('upload_success', true)
            ->with('upload_filename', $diskName);
    }

    /**
     * View a single lesson plan with its details, votes, and version history.
     */
    public function show(LessonPlan $lessonPlan)
    {
        $lessonPlan->load(['author', 'votes']);

        // Get all versions in this plan's family
        $versions = $lessonPlan->familyVersions()
            ->with('author')
            ->get();

        // Check if the current user has already voted on this version
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
     * Pre-fills metadata from the parent version.
     */
    public function edit(LessonPlan $lessonPlan)
    {
        return view('lesson-plans.edit', compact('lessonPlan'));
    }

    /**
     * Store a new version derived from an existing plan.
     */
    public function update(StoreLessonPlanRequest $request, LessonPlan $lessonPlan)
    {
        $data = $request->validated();

        // Generate canonical name for the new version
        $authorName = Auth::user()->name;
        $canonicalName = LessonPlan::generateCanonicalName(
            $data['class_name'],
            $data['lesson_day'],
            $authorName
        );

        // Check for duplicate name
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

        // Compute SHA-256 hash
        $fileHash = hash_file('sha256', $file->getRealPath());

        // Store with canonical filename
        $filePath = $file->storeAs('lessons', $diskName, 'public');

        $newVersion = $lessonPlan->createNewVersion([
            'class_name'    => $data['class_name'],
            'lesson_day'    => $data['lesson_day'],
            'description'   => $data['description'] ?? null,
            'name'          => $canonicalName,
            'author_id'     => Auth::id(),
            'file_path'     => $filePath,
            'file_name'     => $diskName,
            'file_size'     => $fileSize,
            'file_hash'     => $fileHash,
        ]);

        // Send confirmation email to the uploader
        $this->sendUploadConfirmationEmail($newVersion, $diskName);

        return redirect()->route('lesson-plans.show', $newVersion)
            ->with('upload_success', true)
            ->with('upload_filename', $diskName);
    }

    /**
     * Download the file attached to a lesson plan.
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
     */
    public function destroy(LessonPlan $lessonPlan)
    {
        if ($lessonPlan->author_id !== Auth::id()) {
            abort(403, 'You can only delete your own lesson plans.');
        }

        // Delete the file from storage
        if ($lessonPlan->file_path && Storage::disk('public')->exists($lessonPlan->file_path)) {
            Storage::disk('public')->delete($lessonPlan->file_path);
        }

        $lessonPlan->delete();

        return redirect()->route('my-plans')
            ->with('success', 'Lesson plan deleted.');
    }

    /**
     * Send an upload confirmation email to the authenticated user.
     * Wrapped in try/catch so a mail failure never blocks the upload.
     */
    private function sendUploadConfirmationEmail(LessonPlan $plan, string $diskName): void
    {
        try {
            $user = Auth::user();
            Mail::to($user->email)->send(new LessonPlanUploaded(
                recipientName:    $user->name,
                canonicalFilename: $diskName,
                className:        $plan->class_name,
                lessonDay:        $plan->lesson_day,
                versionNumber:    $plan->version_number,
                viewUrl:          route('lesson-plans.show', $plan),
            ));
        } catch (\Exception $e) {
            // Log but don't fail — the upload itself was successful
            \Illuminate\Support\Facades\Log::warning('Upload confirmation email failed: ' . $e->getMessage());
        }
    }
}
