<?php

namespace App\Http\Controllers;

use App\Mail\LessonPlanUploaded;
use App\Models\Favorite;
use App\Models\LessonPlan;
use App\Models\LessonPlanEngagement;
use App\Models\LessonPlanView;
use App\Models\User;
use App\Http\Requests\StoreLessonPlanRequest;
use App\Http\Requests\StoreVersionRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\ValidationException;

/**
 * Handles CRUD operations for lesson plans.
 *
 * Key behaviors:
 * - Upload creates a new lesson plan with a semantic version (1.0.0 for the first
 *   upload of a class/day, or 1.N.0 for a major revision on an existing one).
 * - "New Version" creates a child version; the uploader selects major or minor revision.
 * - Files are renamed on disk to the canonical naming format:
 *   {ClassName}_Day{N}_{AuthorName}_{YYYYMMDD_HHMMSS}UTC_v{major}-{minor}-{patch}.{ext}
 * - Semantic versions are assigned GLOBALLY per (class_name, lesson_day) pair,
 *   enforced by a DB unique index.
 * - SHA-256 file hash is computed on upload for duplicate content detection.
 * - Upload confirmation emails are sent asynchronously (failures logged, not blocking).
 *
 * DESIGN DECISION — Author locked to logged-in user:
 *   The author is always set to the currently authenticated user (Auth::id()).
 *
 * DESIGN DECISION — Semantic versioning is global per class/day:
 *   Unlike the old per-family version_number, semantic versions are shared
 *   across all uploads for a given (class_name, lesson_day) pair regardless
 *   of author or version family. This means Mathematics Day 5 has one global
 *   sequence: 1.0.0 → 1.1.0 → 1.1.1 → 1.2.0 etc.
 */
class LessonPlanController extends Controller
{
    use AuthorizesRequests;

    /**
     * Allowed class names for the upload dropdown (seed list).
     *
     * create() and edit() merge this with distinct class_name values from the DB
     * via buildClassNames(), so any class that already exists in the archive
     * also appears even if it's not in this seed list.
     */
    private const CLASS_NAMES = ['English', 'History', 'Mathematics', 'Science'];

    /**
     * Build the sorted class-name list for upload/edit dropdowns.
     *
     * Merges the hardcoded seed list with distinct class names that already exist
     * in the DB. Sorted alphabetically; duplicates removed.
     * This satisfies the spec requirement that the dropdown includes all classes
     * that teachers have previously uploaded, not just the seed list.
     */
    private function buildClassNames(): array
    {
        $dbClasses = LessonPlan::distinct()->orderBy('class_name')->pluck('class_name')->toArray();
        $merged    = array_values(array_unique(array_merge(self::CLASS_NAMES, $dbClasses)));
        sort($merged);
        return $merged;
    }

    /**
     * Compute the next semantic version for a given class/day pair.
     *
     * Looks at ALL existing plans for (class_name, lesson_day) globally
     * (across all version families and authors) to find the current maximum.
     *
     * Rules:
     * - If no plans exist for this class/day: return [1, 0, 0]
     * - If 'minor' revision type: increment patch of the current max minor
     * - If 'major' revision type (default): increment minor, reset patch to 0
     *
     * The unique DB index on (class_name, lesson_day, version_major, version_minor,
     * version_patch) is the definitive race-condition guard — if two concurrent
     * uploads compute the same version, the second INSERT fails with '23000'.
     */
    private function computeNextSemanticVersion(
        string $className,
        int $lessonDay,
        string $revisionType = 'major'
    ): array {
        // Find the row with the highest semantic version for this class/day.
        // ORDER BY minor DESC, patch DESC gives the "latest" version row.
        $latest = DB::table('lesson_plans')
            ->where('class_name', $className)
            ->where('lesson_day', $lessonDay)
            ->orderByDesc('version_minor')
            ->orderByDesc('version_patch')
            ->select('version_minor', 'version_patch')
            ->first();

        if (!$latest) {
            // No plans yet for this class/day — first upload is always 1.0.0
            return [1, 0, 0];
        }

        if ($revisionType === 'minor') {
            // Bump the patch number; keep the current max minor
            return [1, $latest->version_minor, $latest->version_patch + 1];
        }

        // 'major': bump the minor number, reset patch to 0
        return [1, $latest->version_minor + 1, 0];
    }

    /**
     * Validate the uploaded file's derived extension against the allowed list,
     * store it under the canonical name, and return file metadata for DB persistence.
     *
     * Uses $file->extension() (derived from the MIME type via finfo) rather than
     * $file->getClientOriginalExtension() (which trusts the client-supplied filename).
     * This prevents extension spoofing — a file renamed to .docx but containing
     * executable content would have its true MIME-derived extension checked here.
     *
     * StoreLessonPlanRequest already validates MIME types; this is a second defence layer
     * that also ensures the stored filename extension reflects the actual file type.
     *
     * @return array{diskName: string, fileSize: int, fileHash: string, filePath: string}
     * @throws ValidationException if the derived extension is not in the allowlist
     */
    private function persistUploadedFile(UploadedFile $file, string $canonicalName): array
    {
        $extension = strtolower($file->extension() ?: '');

        if (!in_array($extension, ['doc', 'docx', 'txt', 'rtf', 'odt'], true)) {
            throw ValidationException::withMessages([
                'file' => ['Invalid file type.'],
            ]);
        }

        $diskName = $canonicalName . '.' . $extension;
        $filePath = $file->storeAs('lessons', $diskName, 'public');

        return [
            'diskName' => $diskName,
            'fileSize' => $file->getSize(),
            'fileHash' => hash_file('sha256', $file->getRealPath()),
            'filePath' => $filePath,
        ];
    }

    /**
     * AJAX endpoint: return the computed next semantic version for a class/day.
     *
     * Used by the create and edit forms to show a live version preview before
     * the user submits. Returns JSON: { "version": "1.2.0" }
     *
     * Query parameters:
     * - class_name:    Subject name
     * - lesson_day:    Lesson number (integer)
     * - revision_type: 'major' (default) or 'minor'
     */
    public function nextVersion(Request $request): JsonResponse
    {
        $className    = $request->input('class_name', '');
        $lessonDay    = (int) $request->input('lesson_day', 0);
        // Clamp to valid values; any other input silently defaults to 'major'
        $revisionType = in_array($request->input('revision_type'), ['major', 'minor'], true)
            ? $request->input('revision_type')
            : 'major';

        if (!$className || !$lessonDay) {
            return response()->json(['version' => '1.0.0']);
        }

        [$major, $minor, $patch] = $this->computeNextSemanticVersion(
            $className, $lessonDay, $revisionType
        );

        return response()->json(['version' => "{$major}.{$minor}.{$patch}"]);
    }

    /**
     * AJAX endpoint: return the lowest unused lesson day number for a class.
     *
     * Scans all existing lesson_day values for the given class_name and returns
     * the first positive integer not already taken. Used by the create form
     * duplicate-warning dialog (option b: "use next available day").
     *
     * Query parameters:
     * - class_name: Subject name
     */
    public function nextAvailableDay(Request $request): JsonResponse
    {
        $className = $request->input('class_name', '');
        if (!$className) {
            return response()->json(['next_day' => 1]);
        }

        $existingDays = DB::table('lesson_plans')
            ->where('class_name', $className)
            ->distinct()
            ->orderBy('lesson_day')
            ->pluck('lesson_day')
            ->toArray();

        $nextDay = 1;
        while (in_array($nextDay, $existingDays, true)) {
            $nextDay++;
        }

        return response()->json(['next_day' => $nextDay]);
    }

    /**
     * Archive (rename) plans for a given class/day.
     *
     * Called by the duplicate-warning dialog (option c) when a teacher wants
     * to mark existing plans as superseded before uploading a replacement.
     * Appends "_deleted_YYYYMMDD_HHMMSSz" to each file's name on disk and
     * in the database. DB records are kept; only filenames are changed.
     *
     * Authorization:
     *   - Admins can archive ALL plans in the class/day.
     *   - Non-admins can only archive plans they authored (not other teachers').
     *
     * Consistency: the disk rename is attempted first for each plan. The DB row
     * is only updated after the rename succeeds. A rename failure is logged and
     * that plan is skipped, leaving its DB record pointing to the original path.
     */
    public function retireForClassDay(Request $request): JsonResponse
    {
        $request->validate([
            'class_name' => ['required', 'string', 'max:255'],
            'lesson_day' => ['required', 'integer', 'min:1'],
        ]);

        $className = $request->input('class_name');
        $lessonDay = (int) $request->input('lesson_day');

        $currentUserId = Auth::id();
        $isAdmin       = Auth::user()->is_admin ?? false;

        $allPlans = LessonPlan::where('class_name', $className)
            ->where('lesson_day', $lessonDay)
            ->get();

        if ($allPlans->isEmpty()) {
            return response()->json(['success' => true, 'count' => 0]);
        }

        // Non-admins can only archive their own plans, not other teachers' plans.
        if ($isAdmin) {
            $plansToArchive = $allPlans;
        } else {
            $plansToArchive = $allPlans->where('author_id', $currentUserId);
            if ($plansToArchive->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only archive plans that you have authored.',
                ], 403);
            }
        }

        // e.g. "_deleted_20260228_153045Z"
        $suffix = '_deleted_' . now()->utc()->format('Ymd_His') . 'Z';
        $count  = 0;

        // Per-plan: rename the file on disk FIRST, then update the DB row.
        // This keeps disk and DB in sync — a failed rename leaves the DB row
        // pointing to the original path (no stale path written to DB).
        foreach ($plansToArchive as $plan) {
            $ext     = pathinfo($plan->file_name ?? '', PATHINFO_EXTENSION);
            $base    = pathinfo($plan->file_name ?? '', PATHINFO_FILENAME);
            $newName = $base . $suffix . ($ext ? '.' . $ext : '');
            $newPath = 'lessons/' . $newName;

            if ($plan->file_path) {
                try {
                    if (Storage::disk('public')->exists($plan->file_path)) {
                        Storage::disk('public')->move($plan->file_path, $newPath);
                        // Only update file_path/file_name when the move actually succeeded —
                        // writing a new path to the DB when no rename happened would desync
                        // the DB from the filesystem.
                        DB::table('lesson_plans')->where('id', $plan->id)->update([
                            'file_name' => $newName,
                            'file_path' => $newPath,
                            'name'      => $plan->name . $suffix,
                        ]);
                    } else {
                        // File missing from disk (e.g. manually removed).
                        // Keep the original file_path to avoid a stale/wrong path in the DB;
                        // still mark the display name so the plan appears retired.
                        \Illuminate\Support\Facades\Log::warning(
                            "retireForClassDay: file missing on disk for plan {$plan->id} "
                            . "(path: {$plan->file_path}) – name marked retired, file_path unchanged."
                        );
                        DB::table('lesson_plans')->where('id', $plan->id)->update([
                            'name' => $plan->name . $suffix,
                        ]);
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::warning(
                        "retireForClassDay: disk rename failed for plan {$plan->id} – {$e->getMessage()}"
                    );
                    continue; // leave DB unchanged so it still matches old disk path
                }
            } else {
                // No file — just append the suffix to the display name.
                DB::table('lesson_plans')->where('id', $plan->id)->update([
                    'name' => $plan->name . $suffix,
                ]);
            }

            $count++;
        }

        return response()->json(['success' => true, 'count' => $count]);
    }

    /**
     * Show the upload form for a brand-new lesson plan.
     */
    public function create()
    {
        $classNames    = $this->buildClassNames();
        $lessonNumbers = range(1, 20);

        return view('lesson-plans.create', compact('classNames', 'lessonNumbers'));
    }

    /**
     * Store a brand-new lesson plan.
     *
     * The semantic version is computed globally for the selected class/day.
     * New uploads are always treated as "major" revisions (or 1.0.0 if first).
     */
    public function store(StoreLessonPlanRequest $request)
    {
        $data = $request->validated();

        $author = Auth::user();

        // Compute next semantic version globally for this class/day.
        // New uploads always use 'major' (first upload = 1.0.0).
        [$major, $minor, $patch] = $this->computeNextSemanticVersion(
            $data['class_name'],
            $data['lesson_day'],
            'major'
        );

        $canonicalName = LessonPlan::generateCanonicalName(
            $data['class_name'],
            $data['lesson_day'],
            $author->name,
            null,
            [$major, $minor, $patch]
        );

        // Guard: reject if this exact canonical name already exists
        if (LessonPlan::where('name', $canonicalName)->exists()) {
            return back()->withInput()->with('error',
                'A lesson plan with the name "' . $canonicalName . '" already exists. '
                . 'Please wait a moment and try again.');
        }

        $upload = $this->persistUploadedFile($request->file('file'), $canonicalName);

        try {
            $attrs = $this->buildPlanAttributes($data, $canonicalName, $author, $upload, $major, $minor, $patch);
            // Only the very first plan for a class/day becomes Official automatically.
            // If plans already exist (another teacher uploaded first), the existing
            // Official designation is preserved — admin must manually reassign if needed.
            $attrs['is_official'] = !LessonPlan::where('class_name', $data['class_name'])
                ->where('lesson_day', $data['lesson_day'])
                ->exists();
            $plan = LessonPlan::create($attrs);
        } catch (\Illuminate\Database\QueryException $e) {
            Storage::disk('public')->delete($upload['filePath']);
            if ($e->getCode() === '23000') {
                return back()->withInput()->with('error',
                    'A version conflict occurred (another upload happened simultaneously). Please try again.');
            }
            throw $e;
        }

        $this->sendUploadConfirmationEmail($plan, $upload['diskName']);

        return redirect()->route('lesson-plans.show', $plan)
            ->with('upload_success', true)
            ->with('upload_filename', $upload['diskName']);
    }

    /**
     * View a single lesson plan with its details, votes, and version history.
     *
     * Requires authentication + verified email (per spec Section 3.5).
     */
    public function show(LessonPlan $lessonPlan)
    {
        $lessonPlan->load(['author', 'votes']);

        $versions = $lessonPlan->familyVersions()
            ->with('author')
            ->get();

        $userVote   = null;
        $hasEngaged = false;
        if (Auth::check()) {
            $userVote = $lessonPlan->votes()
                ->where('user_id', Auth::id())
                ->first();

            // Record page view (informational metric; kept for historical data)
            LessonPlanView::firstOrCreate([
                'user_id'        => Auth::id(),
                'lesson_plan_id' => $lessonPlan->id,
            ]);

            // Engagement check: only non-authors can vote; they must have
            // downloaded the plan or opened it in an external viewer first.
            // Authors see a "cannot vote on own plan" notice instead.
            $hasEngaged = $lessonPlan->author_id !== Auth::id()
                && LessonPlanEngagement::where('lesson_plan_id', $lessonPlan->id)
                    ->where('user_id', Auth::id())
                    ->exists();

            $isFavorited = Favorite::where('lesson_plan_id', $lessonPlan->id)
                ->where('user_id', Auth::id())
                ->exists();
        }

        $isAuthorOfPlan = Auth::check() && $lessonPlan->author_id === Auth::id();

        return view('lesson-plans.show', compact('lessonPlan', 'versions', 'userVote', 'hasEngaged', 'isAuthorOfPlan', 'isFavorited'));
    }

    /**
     * Compare the currently viewed version against another version in the same family.
     *
     * This MVP supports line-level diffs for .txt files only.
     * Other formats return a clear "not yet supported" message instead of failing.
     */
    public function compare(Request $request, LessonPlan $lessonPlan)
    {
        $lessonPlan->load('author');

        $versions = $lessonPlan->familyVersions()
            ->with('author')
            ->get()
            ->sortBy('created_at')
            ->values();

        $targetPlan = $this->resolveCompareTarget($lessonPlan, $versions, $request->query('compare_to'));

        $diffSummary = null;
        $diffOps = [];
        $warning = null;

        if ($targetPlan) {
            [$newSupported, $newLines, $newError] = $this->readPlanLinesForDiff($lessonPlan);
            [$oldSupported, $oldLines, $oldError] = $this->readPlanLinesForDiff($targetPlan);

            if (! $newSupported || ! $oldSupported) {
                $warning = $newError ?: $oldError;
            } else {
                if (count($oldLines) > 2000 || count($newLines) > 2000) {
                    $warning = 'Comparison unavailable: selected text files are too large for inline diff. Please compare these files locally.';
                } else {
                    $diffOps     = $this->buildLineDiffOperations($oldLines, $newLines);
                    $diffSummary = $this->buildDiffSummary($diffOps);
                }
            }
        } else {
            $warning = 'No previous version is available to compare against yet.';
        }

        return view('lesson-plans.compare', [
            'lessonPlan'   => $lessonPlan,
            'versions'     => $versions,
            'targetPlan'   => $targetPlan,
            'diffSummary'  => $diffSummary,
            'diffOps'      => $diffOps,
            'warning'      => $warning,
        ]);
    }

    /**
     * Show the form to create a new version of an existing plan.
     *
     * Pre-computes the next major and minor version so the form can
     * display a live preview without an extra AJAX round-trip on load.
     */
    public function edit(LessonPlan $lessonPlan)
    {
        $classNames    = $this->buildClassNames();
        $lessonNumbers = range(1, 20);

        [$mj, $mn, $mp] = $this->computeNextSemanticVersion(
            $lessonPlan->class_name, $lessonPlan->lesson_day, 'major'
        );
        $nextMajorVersion = "{$mj}.{$mn}.{$mp}";

        [$mj, $mn, $mp] = $this->computeNextSemanticVersion(
            $lessonPlan->class_name, $lessonPlan->lesson_day, 'minor'
        );
        $nextMinorVersion = "{$mj}.{$mn}.{$mp}";

        return view('lesson-plans.edit', compact(
            'lessonPlan', 'classNames', 'lessonNumbers',
            'nextMajorVersion', 'nextMinorVersion'
        ));
    }

    /**
     * Store a new version derived from an existing plan.
     *
     * Per spec Section 2.5: if the uploader is the same author as the parent plan,
     * the new file is linked into the parent's version family (original_id / parent_id).
     * If the uploader is a DIFFERENT user, a completely independent plan is created
     * (version_number 1, no family linkage).
     *
     * In both cases, the semantic version is computed GLOBALLY for the selected
     * class/day using the user's chosen revision_type (major or minor).
     */
    public function storeVersion(StoreVersionRequest $request, LessonPlan $lessonPlan)
    {
        $data = $request->validated();

        $author = Auth::user();

        $revisionType = $data['revision_type'];
        [$major, $minor, $patch] = $this->computeNextSemanticVersion(
            $data['class_name'],
            $data['lesson_day'],
            $revisionType
        );

        $canonicalName = LessonPlan::generateCanonicalName(
            $data['class_name'],
            $data['lesson_day'],
            $author->name,
            null,
            [$major, $minor, $patch]
        );

        if (LessonPlan::where('name', $canonicalName)->exists()) {
            return back()->withInput()->with('error',
                'A lesson plan with the name "' . $canonicalName . '" already exists. '
                . 'Please wait a moment and try again.');
        }

        $upload = $this->persistUploadedFile($request->file('file'), $canonicalName);

        // ── Author check: link to family or create standalone ──
        if ($lessonPlan->author_id !== $author->id) {
            try {
                $newPlan = LessonPlan::create(
                    $this->buildPlanAttributes($data, $canonicalName, $author, $upload, $major, $minor, $patch)
                );
            } catch (\Illuminate\Database\QueryException $e) {
                Storage::disk('public')->delete($upload['filePath']);
                if ($e->getCode() === '23000') {
                    return back()->withInput()->with('error',
                        'A version conflict occurred. Please try again.');
                }
                throw $e;
            }
            $this->sendUploadConfirmationEmail($newPlan, $upload['diskName']);
            return redirect()->route('dashboard')
                ->with('upload_success', true)
                ->with('upload_filename', $upload['diskName'])
                ->with('status', 'Your plan was saved as a new standalone plan (you are not the original author).');
        }

        // Same author — create new version linked to the parent's family
        try {
            $newVersion = $lessonPlan->createNewVersion([
                'class_name'    => $data['class_name'],
                'lesson_day'    => $data['lesson_day'],
                'description'   => $data['description'] ?? null,
                'name'          => $canonicalName,
                'author_id'     => $author->id,
                'file_path'     => $upload['filePath'],
                'file_name'     => $upload['diskName'],
                'file_size'     => $upload['fileSize'],
                'file_hash'     => $upload['fileHash'],
                'version_major' => $major,
                'version_minor' => $minor,
                'version_patch' => $patch,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            Storage::disk('public')->delete($upload['filePath']);
            if ($e->getCode() === '23000') {
                return back()->withInput()->with('error',
                    'A version conflict occurred. Please try again.');
            }
            throw $e;
        }

        $this->sendUploadConfirmationEmail($newVersion, $upload['diskName']);

        return redirect()->route('dashboard')
            ->with('upload_success', true)
            ->with('upload_filename', $upload['diskName']);
    }

    /**
     * Download the file attached to a lesson plan.
     *
     * Records a download engagement record for the authenticated user,
     * which unlocks voting on this plan version.
     */
    public function download(LessonPlan $lessonPlan)
    {
        if (!$lessonPlan->file_path || !Storage::disk('public')->exists($lessonPlan->file_path)) {
            return back()->with('error', 'File not found.');
        }

        // Track download as an engagement event (unlocks voting for this user)
        if (Auth::check()) {
            LessonPlanEngagement::firstOrCreate([
                'user_id'        => Auth::id(),
                'lesson_plan_id' => $lessonPlan->id,
                'type'           => LessonPlanEngagement::DOWNLOAD,
            ]);
        }

        return Storage::disk('public')->download(
            $lessonPlan->file_path,
            $lessonPlan->file_name ?? basename($lessonPlan->file_path)
        );
    }

    /**
     * AJAX: Record that the user opened this plan in an external viewer.
     *
     * Called client-side when the user clicks any external viewer button.
     * The engagement record unlocks voting on this plan for the user.
     */
    public function trackEngagement(Request $request, LessonPlan $lessonPlan): JsonResponse
    {
        $data = $request->validate([
            'type' => 'required|in:google_docs,ms_office',
        ]);

        LessonPlanEngagement::firstOrCreate([
            'user_id'        => Auth::id(),
            'lesson_plan_id' => $lessonPlan->id,
            'type'           => $data['type'],
        ]);

        return response()->json(['ok' => true]);
    }

    /**
     * Delete a lesson plan (only the author can delete their own).
     *
     * Guards against deleting plans with descendants (must delete leaf-first).
     * Any unexpected exception is caught, logged with a full stack trace, and
     * returned to the user as a readable error message instead of a blank 500.
     */
    public function destroy(LessonPlan $lessonPlan)
    {
        // LessonPlanPolicy::delete() — allows author; LessonPlanPolicy::before() gives admins a pass.
        // authorize() is kept outside try/catch: AuthorizationException renders as 403 (not 500).
        $this->authorize('delete', $lessonPlan);

        try {
            // Guard: official plans must have their designation reassigned before deletion.
            if ($lessonPlan->is_official) {
                return back()->with('error',
                    'This plan is the Official version for its class/lesson. '
                    . 'Please mark a different version as Official before deleting this one.');
            }

            // Guard: root plans cannot be deleted while they still have descendants.
            if ($lessonPlan->is_original) {
                $hasDescendants = LessonPlan::where('original_id', $lessonPlan->id)->exists();
                if ($hasDescendants) {
                    return back()->with('error',
                        'This is the original version and other versions are based on it. '
                        . 'Please delete the newer versions first.');
                }
            }

            // Guard: non-root plans cannot be deleted while they still have children.
            if (!$lessonPlan->is_original && $lessonPlan->children()->exists()) {
                return back()->with('error',
                    'Other versions were created from this one. '
                    . 'Please delete those newer versions first.');
            }

            if ($lessonPlan->file_path && Storage::disk('public')->exists($lessonPlan->file_path)) {
                Storage::disk('public')->delete($lessonPlan->file_path);
            }

            // votes, favorites, lesson_plan_views, lesson_plan_engagements all CASCADE on delete.
            $lessonPlan->delete();

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error(
                "destroy() failed for plan {$lessonPlan->id} "
                . "(v{$lessonPlan->semantic_version} {$lessonPlan->class_name} "
                . "Lesson {$lessonPlan->lesson_day}): "
                . get_class($e) . ': ' . $e->getMessage()
                . "\n" . $e->getTraceAsString()
            );
            return back()->with('error',
                'Could not delete this lesson plan. Details have been logged.');
        }

        return redirect()->route('dashboard')
            ->with('success', 'Lesson plan deleted.');
    }

    /**
     * Build the attribute array for creating a standalone lesson plan record.
     *
     * Used by both store() (first upload) and storeVersion() (different-author fork).
     * The same-author version-chain path uses LessonPlan::createNewVersion() instead,
     * which adds original_id / parent_id linkage automatically.
     */
    private function buildPlanAttributes(
        array $data,
        string $canonicalName,
        User $author,
        array $upload,
        int $major,
        int $minor,
        int $patch
    ): array {
        return [
            'class_name'     => $data['class_name'],
            'lesson_day'     => $data['lesson_day'],
            'description'    => $data['description'] ?? null,
            'name'           => $canonicalName,
            'author_id'      => $author->id,
            'version_number' => 1,
            'version_major'  => $major,
            'version_minor'  => $minor,
            'version_patch'  => $patch,
            'file_path'      => $upload['filePath'],
            'file_name'      => $upload['diskName'],
            'file_size'      => $upload['fileSize'],
            'file_hash'      => $upload['fileHash'],
        ];
    }

    /**
     * Send an upload confirmation email to the authenticated user (the uploader).
     *
     * Wrapped in try/catch so a mail failure never blocks the upload itself.
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
                semanticVersion:   $plan->semantic_version,
                viewUrl:           route('lesson-plans.show', $plan),
            ));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning(
                'Upload confirmation email failed: ' . $e->getMessage()
            );
        }
    }

    /**
     * Resolve the comparison target from query string or default to immediate predecessor.
     *
     * Only plans within the same version family are accepted as targets; a foreign
     * plan ID supplied via ?compare_to= is silently ignored and falls back to the
     * previous in-family version.
     *
     * @param \Illuminate\Support\Collection<int, LessonPlan> $versions
     */
    private function resolveCompareTarget(LessonPlan $lessonPlan, $versions, mixed $compareTo): ?LessonPlan
    {
        if ($compareTo !== null) {
            $requestedId = (int) $compareTo;
            $requested = $versions->first(fn (LessonPlan $version) => $version->id === $requestedId);
            if ($requested && $requested->id !== $lessonPlan->id) {
                return $requested;
            }
        }

        $index = $versions->search(fn (LessonPlan $version) => $version->id === $lessonPlan->id);
        if ($index === false || $index === 0) {
            return null;
        }

        return $versions->get($index - 1);
    }

    /**
     * Read a lesson plan into comparable lines.
     *
     * Supports only .txt files in this MVP. Other extensions return a warning reason.
     * Line endings are normalised to \n before splitting.
     *
     * @return array{0: bool, 1: array<int, string>, 2: string|null}
     */
    private function readPlanLinesForDiff(LessonPlan $plan): array
    {
        if (! $plan->file_path) {
            return [false, [], 'Comparison unavailable: one of the selected versions has no file path.'];
        }

        if (! Storage::disk('public')->exists($plan->file_path)) {
            return [false, [], 'Comparison unavailable: one of the selected files is missing from storage.'];
        }

        $extension = strtolower(pathinfo($plan->file_name ?? $plan->file_path, PATHINFO_EXTENSION));
        if ($extension !== 'txt') {
            return [false, [], 'Comparison currently supports .txt files only. DOC/DOCX/RTF/ODT support is planned next.'];
        }

        $absolutePath = Storage::disk('public')->path($plan->file_path);
        $content = @file_get_contents($absolutePath);
        if ($content === false) {
            return [false, [], 'Comparison failed: could not read one of the selected files.'];
        }

        $normalized = str_replace(["\r\n", "\r"], "\n", $content);
        return [true, explode("\n", $normalized), null];
    }

    /**
     * Build line-level diff operations via a longest-common-subsequence matrix.
     *
     * Returns an array of operations, each with 'type' (equal | add | remove)
     * and 'line' (the text of that line).
     *
     * @param array<int, string> $oldLines
     * @param array<int, string> $newLines
     * @return array<int, array{type: string, line: string}>
     */
    private function buildLineDiffOperations(array $oldLines, array $newLines): array
    {
        $oldCount = count($oldLines);
        $newCount = count($newLines);

        $lcs = array_fill(0, $oldCount + 1, array_fill(0, $newCount + 1, 0));

        for ($i = $oldCount - 1; $i >= 0; $i--) {
            for ($j = $newCount - 1; $j >= 0; $j--) {
                if ($oldLines[$i] === $newLines[$j]) {
                    $lcs[$i][$j] = $lcs[$i + 1][$j + 1] + 1;
                } else {
                    $lcs[$i][$j] = max($lcs[$i + 1][$j], $lcs[$i][$j + 1]);
                }
            }
        }

        $ops = [];
        $i = 0;
        $j = 0;
        while ($i < $oldCount && $j < $newCount) {
            if ($oldLines[$i] === $newLines[$j]) {
                $ops[] = ['type' => 'equal', 'line' => $oldLines[$i]];
                $i++;
                $j++;
                continue;
            }

            if ($lcs[$i + 1][$j] >= $lcs[$i][$j + 1]) {
                $ops[] = ['type' => 'remove', 'line' => $oldLines[$i]];
                $i++;
            } else {
                $ops[] = ['type' => 'add', 'line' => $newLines[$j]];
                $j++;
            }
        }

        while ($i < $oldCount) {
            $ops[] = ['type' => 'remove', 'line' => $oldLines[$i]];
            $i++;
        }
        while ($j < $newCount) {
            $ops[] = ['type' => 'add', 'line' => $newLines[$j]];
            $j++;
        }

        return $ops;
    }

    /**
     * Build high-level summary counts from line-level operations.
     *
     * 'changed' is an approximation: the smaller of added vs removed counts,
     * since paired add/remove sequences typically represent modified lines.
     *
     * @param array<int, array{type: string, line: string}> $ops
     * @return array{added: int, removed: int, changed: int}
     */
    private function buildDiffSummary(array $ops): array
    {
        $added = 0;
        $removed = 0;

        foreach ($ops as $op) {
            if ($op['type'] === 'add') {
                $added++;
            } elseif ($op['type'] === 'remove') {
                $removed++;
            }
        }

        return [
            'added'   => $added,
            'removed' => $removed,
            // Approximation: paired adds/removes typically represent modified lines.
            'changed' => min($added, $removed),
        ];
    }
}
