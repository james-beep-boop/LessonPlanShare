<?php

namespace App\Http\Controllers;

use App\Models\LessonPlan;
use App\Models\User;
use App\Traits\DiffHelperTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

/**
 * Admin panel controller.
 *
 * All routes here require auth + verified + is_admin (AdminMiddleware).
 * Admins can delete any lesson plan or any user account, bypassing
 * the normal author-only restrictions.
 *
 * SUPER_ADMIN_EMAIL is the one account that can revoke admin privileges.
 * All other admins can only grant privileges.
 */
class AdminController extends Controller
{
    use DiffHelperTrait;

    /** Display the admin panel with searchable/sortable tables and counters. */
    public function index(Request $request)
    {
        // ── Lesson Plans: search + sort ──
        $planSearch = $request->input('plan_search', '');
        $planSort   = $request->input('plan_sort', 'class_name');
        $planOrder  = in_array(strtolower($request->input('plan_order', 'asc')), ['asc', 'desc'])
            ? strtolower($request->input('plan_order', 'asc'))
            : 'asc';
        $allowedPlanSorts = ['is_official', 'class_name', 'grade', 'lesson_day', 'description', 'author_name', 'semantic_version', 'updated_at'];

        $plansQuery = LessonPlan::query()
            ->leftJoin('users', 'users.id', '=', 'lesson_plans.author_id')
            ->select('lesson_plans.*', 'users.name as author_name');

        if ($planSearch) {
            $plansQuery->where(function ($q) use ($planSearch) {
                $q->where('lesson_plans.class_name', 'like', "%{$planSearch}%")
                  ->orWhere('lesson_plans.description', 'like', "%{$planSearch}%")
                  ->orWhere('lesson_plans.name', 'like', "%{$planSearch}%")
                  ->orWhere('users.name', 'like', "%{$planSearch}%");
            });
        }

        if (in_array($planSort, $allowedPlanSorts)) {
            if ($planSort === 'author_name') {
                $plansQuery->orderBy('users.name', $planOrder);
            } elseif ($planSort === 'semantic_version') {
                $plansQuery->orderBy('lesson_plans.version_major', $planOrder)
                           ->orderBy('lesson_plans.version_minor', $planOrder)
                           ->orderBy('lesson_plans.version_patch', $planOrder);
            } else {
                $plansQuery->orderBy('lesson_plans.' . $planSort, $planOrder);
            }
        } else {
            // Invalid sort field — fall back to the same default as the initial page load
            $plansQuery->orderBy('lesson_plans.class_name', 'asc');
        }

        $plans = $plansQuery->paginate(20, ['*'], 'plans_page')->withQueryString();

        // ── Users: search + sort ──
        $userSearch = $request->input('user_search', '');
        $userSort   = $request->input('user_sort', 'name');
        $userOrder  = in_array(strtolower($request->input('user_order', 'asc')), ['asc', 'desc'])
            ? strtolower($request->input('user_order', 'asc'))
            : 'asc';
        $allowedUserSorts = ['name', 'email', 'created_at', 'email_verified_at'];

        $usersQuery = User::query();

        if ($userSearch) {
            $usersQuery->where(function ($q) use ($userSearch) {
                $q->where('name', 'like', "%{$userSearch}%")
                  ->orWhere('email', 'like', "%{$userSearch}%");
            });
        }

        if (in_array($userSort, $allowedUserSorts)) {
            $usersQuery->orderBy($userSort, $userOrder);
        } else {
            // Invalid sort field — fall back to the same default as the initial page load
            $usersQuery->orderBy('name', 'asc');
        }

        $users = $usersQuery->paginate(20, ['*'], 'users_page')->withQueryString();

        // ── Official plans table (sorted by class_name, lesson_day) ──
        $officialPlans = LessonPlan::query()
            ->leftJoin('users', 'users.id', '=', 'lesson_plans.author_id')
            ->select('lesson_plans.*', 'users.name as author_name')
            ->where('lesson_plans.is_official', true)
            ->orderBy('lesson_plans.class_name', 'asc')
            ->orderBy('lesson_plans.lesson_day', 'asc')
            ->get();

        // ── All plans (non-paginated) for client-side relocate + compare tables ──
        $allPlansFlat = LessonPlan::query()
            ->leftJoin('users', 'users.id', '=', 'lesson_plans.author_id')
            ->select(
                'lesson_plans.id', 'lesson_plans.class_name', 'lesson_plans.grade',
                'lesson_plans.lesson_day', 'lesson_plans.is_official',
                'lesson_plans.file_name', 'lesson_plans.file_path',
                'lesson_plans.version_major', 'lesson_plans.version_minor',
                'lesson_plans.version_patch', 'lesson_plans.original_id',
                'lesson_plans.description', 'lesson_plans.updated_at',
                'users.name as author_name'
            )
            ->orderByDesc('lesson_plans.is_official')
            ->orderBy('lesson_plans.class_name')
            ->orderBy('lesson_plans.grade')
            ->orderBy('lesson_plans.lesson_day')
            ->orderByDesc('lesson_plans.version_minor')
            ->orderByDesc('lesson_plans.version_patch')
            ->get()
            ->map(fn ($p) => [
                'id'          => $p->id,
                'class_name'  => $p->class_name,
                'grade'       => $p->grade,
                'lesson_day'  => $p->lesson_day,
                'is_official' => (bool) $p->is_official,
                'file_name'   => $p->file_name,
                'file_path'   => $p->file_path,
                'version'     => "{$p->version_major}.{$p->version_minor}.{$p->version_patch}",
                'original_id' => $p->original_id,
                'description' => $p->description,
                'updated_at'  => $p->updated_at ? Carbon::parse($p->updated_at)->format('d M Y') : '—',
                'author_name' => $p->author_name ?? 'Anonymous',
            ])
            ->values()
            ->all();

        // ── Dropdown options for relocate feature — derived from $allPlansFlat ──
        $classNamesList = array_values(array_unique(array_merge(
            LessonPlan::CLASS_NAMES,
            array_column($allPlansFlat, 'class_name')
        )));
        sort($classNamesList);
        $gradesList = [10, 11, 12];
        $daysList   = array_values(array_unique(array_column($allPlansFlat, 'lesson_day')));
        sort($daysList);

        // ── Summary counters ──
        $uniqueClassCount = LessonPlan::distinct('class_name')->count('class_name');
        $totalPlanCount   = LessonPlan::count();
        $contributorCount = LessonPlan::distinct('author_id')->count('author_id');

        // ── Analytics chart data (weekly cumulative) ──
        $earliestUser = User::min('created_at');
        $earliestPlan = LessonPlan::min('created_at');
        $chartStart   = collect([$earliestUser, $earliestPlan])
            ->filter()
            ->map(fn ($d) => \Carbon\Carbon::parse($d))
            ->min()
            ?->startOfWeek(\Carbon\Carbon::MONDAY)
            ?? now()->subMonths(6)->startOfWeek(\Carbon\Carbon::MONDAY);

        // Build weekly Monday-aligned labels from $chartStart to today
        $chartLabels = [];
        $cursor = $chartStart->copy();
        while ($cursor->lte(now())) {
            $chartLabels[] = $cursor->format('d M');
            $cursor->addWeek();
        }

        // Returns week-start date → count array for any append-only table
        $weekly = fn (string $table, array $where = []) => DB::table($table)
            ->where($where)
            ->selectRaw("DATE(DATE_SUB(created_at, INTERVAL WEEKDAY(created_at) DAY)) as week_start, COUNT(*) as cnt")
            ->groupBy('week_start')
            ->pluck('cnt', 'week_start')
            ->toArray();

        // Converts a week→count map to a cumulative series aligned to $chartLabels
        $toCumulative = function (array $weeklyCounts) use ($chartStart): array {
            $result  = [];
            $running = 0;
            $cur     = $chartStart->copy();
            while ($cur->lte(now())) {
                $running  += $weeklyCounts[$cur->format('Y-m-d')] ?? 0;
                $result[]  = $running;
                $cur->addWeek();
            }
            return $result;
        };

        $userCumulative     = $toCumulative($weekly('users'));
        $loginCumulative    = $toCumulative($weekly('user_logins'));
        $allPlansCumulative = $toCumulative($weekly('lesson_plans'));
        $officialCumulative = $toCumulative($weekly('lesson_plans', ['is_official' => true]));
        $downloadCumulative = $toCumulative($weekly('lesson_plan_downloads'));

        return view('admin.index', compact(
            'plans', 'users', 'officialPlans',
            'uniqueClassCount', 'totalPlanCount', 'contributorCount',
            'planSearch', 'planSort', 'planOrder',
            'userSearch', 'userSort', 'userOrder',
            'chartLabels', 'userCumulative', 'loginCumulative',
            'allPlansCumulative', 'officialCumulative', 'downloadCumulative',
            'allPlansFlat', 'classNamesList', 'gradesList', 'daysList'
        ));
    }

    /** Delete a single lesson plan (admin bypasses author check). */
    public function destroyPlan(LessonPlan $lessonPlan)
    {
        // Official plans must have their designation reassigned before deletion.
        if ($lessonPlan->is_official) {
            return redirect()->route('admin.index')
                ->with('error', 'Cannot delete the Official version. Mark another plan as Official first.');
        }

        try {
            $this->deletePlanFile($lessonPlan);
            $lessonPlan->delete();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error(
                "AdminController::destroyPlan failed for plan {$lessonPlan->id}: "
                . get_class($e) . ': ' . $e->getMessage()
                . "\n" . $e->getTraceAsString()
            );
            return redirect()->route('admin.index')
                ->with('error', 'Could not delete the lesson plan. Details have been logged.');
        }

        return redirect()->route('admin.index')->with('success', 'Lesson plan deleted.');
    }

    /** Bulk-delete multiple lesson plans by ID array. */
    public function bulkDestroyPlans(Request $request)
    {
        $data = $request->validate([
            'ids'   => ['required', 'array'],
            'ids.*' => ['integer', 'exists:lesson_plans,id'],
        ]);
        $ids = $data['ids'];

        $plans = LessonPlan::whereIn('id', $ids)->get();

        // Skip official plans — they cannot be bulk-deleted.
        $officialIds  = $plans->where('is_official', true)->pluck('id');
        $deletable    = $plans->where('is_official', false);

        foreach ($deletable as $plan) {
            $this->deletePlanFile($plan);
            $plan->delete();
        }

        $message = $deletable->count() . ' lesson plan(s) deleted.';
        if ($officialIds->isNotEmpty()) {
            $message .= ' ' . $officialIds->count() . ' Official plan(s) were skipped — reassign Official first.';
        }

        return redirect()->route('admin.index')->with('success', $message);
    }

    /**
     * Grant or revoke admin privileges for a user.
     *
     * Any admin can grant privileges to a non-admin.
     * Only the super-admin (SUPER_ADMIN_EMAIL) can revoke privileges.
     * No admin can modify their own privileges.
     */
    public function toggleAdmin(User $user): RedirectResponse
    {
        if ($user->id === Auth::id()) {
            return back()->with('error', 'You cannot change your own admin status.');
        }

        // Revoking admin is restricted to the super-admin (configured in config/app.php or SUPER_ADMIN_EMAIL env)
        if ($user->is_admin && Auth::user()->email !== config('app.super_admin_email')) {
            return back()->with('error', 'Only the super-administrator can revoke admin privileges.');
        }

        $newStatus = ! $user->is_admin;
        $user->update(['is_admin' => $newStatus]);

        return back()->with('success', $newStatus
            ? "Admin privileges granted to {$user->name}."
            : "Admin privileges revoked from {$user->name}.");
    }

    /**
     * Delete a single user account and all their uploaded lesson plan files.
     *
     * Blocked if the user owns any Official plan — admin must reassign Official
     * to another version before deleting the user.  This prevents a class/day
     * losing its designated official version via cascade.
     *
     * Files are deleted after the DB commit, not inside the transaction, so a
     * commit failure cannot leave orphaned DB rows with already-deleted files on
     * disk.
     *
     * Note: we intentionally do NOT use lockForUpdate() here. A broad lock on all
     * of a user's plans causes InnoDB Next-Key Locks that block concurrent inserts
     * on lesson_plans and creates deadlock risk. The window between the official-
     * count check and the delete is acceptable — setOfficial() runs inside its own
     * serialised transaction and the worst outcome is an edge-case where an Official
     * flag is added just before the delete cascades (already-deleted plan has no
     * further effect). This app's expected load makes that race negligible.
     */
    public function destroyUser(User $user)
    {
        // Prevent self-deletion
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.index')->with('error', 'You cannot delete your own account.');
        }

        $blocked      = false;
        $officialCount = 0;
        $filesToDelete = [];

        DB::transaction(function () use ($user, &$blocked, &$officialCount, &$filesToDelete) {
            $officialCount = LessonPlan::where('author_id', $user->id)
                ->where('is_official', true)
                ->count();

            if ($officialCount > 0) {
                $blocked = true;
                return; // abort the closure; transaction rolls back (no writes happened)
            }

            // Collect file paths before deleting rows. Files are removed after the
            // transaction commits; deleting them inside the transaction risks losing
            // files on disk if the subsequent DB commit fails.
            $plans = LessonPlan::where('author_id', $user->id)->get();
            foreach ($plans as $plan) {
                if ($plan->file_path) {
                    $filesToDelete[] = $plan->file_path;
                }
            }

            $user->delete(); // FK CASCADE removes the lesson_plan rows
        });

        if ($blocked) {
            return redirect()->route('admin.index')->with(
                'error',
                "Cannot delete {$user->name}: they own {$officialCount} Official plan(s). "
                . 'Reassign Official to another version first.'
            );
        }

        // Delete files only after the DB transaction has committed successfully.
        $this->deleteFiles($filesToDelete);

        return redirect()->route('admin.index')->with('success', 'User deleted.');
    }

    /**
     * Bulk-delete multiple user accounts and all their uploaded lesson plan files.
     *
     * Users who own Official plans are skipped (same invariant as destroyUser).
     *
     * Note: we intentionally do NOT use lockForUpdate() here — see destroyUser()
     * docblock for the full rationale. A broad lock across all selected users'
     * plans would create significant deadlock risk under concurrent load.
     */
    public function bulkDestroyUsers(Request $request)
    {
        $data = $request->validate([
            'user_ids'   => ['required', 'array'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ]);
        $ids = array_filter(
            $data['user_ids'],
            fn ($id) => (int) $id !== auth()->id()  // never delete self
        );

        if (empty($ids)) {
            return redirect()->route('admin.index')->with('error', 'No users selected (or only yourself).');
        }

        $deletedCount    = 0;
        $officialOwnerIds = [];
        $filesToDelete   = [];

        DB::transaction(function () use ($ids, &$deletedCount, &$officialOwnerIds, &$filesToDelete) {
            // Load all plans for the selected users, grouped by author_id.
            // No lockForUpdate() — see class docblock for rationale.
            $allPlans = LessonPlan::whereIn('author_id', $ids)->get()->groupBy('author_id');

            // Identify which selected users own Official plans — skip them.
            $officialOwnerIds = $allPlans
                ->filter(fn ($group) => $group->where('is_official', true)->isNotEmpty())
                ->keys()
                ->toArray();

            $deletableIds = array_values(array_diff($ids, $officialOwnerIds));

            // Collect file paths before deleting rows. Files are removed after the
            // transaction commits; deleting them inside the transaction risks losing
            // files on disk if the subsequent DB commit fails.
            $users = User::whereIn('id', $deletableIds)->get();
            foreach ($users as $user) {
                $plans = $allPlans->get($user->id, collect());
                foreach ($plans as $plan) {
                    if ($plan->file_path) {
                        $filesToDelete[] = $plan->file_path;
                    }
                }
                $user->delete(); // FK CASCADE removes the lesson_plan rows
                $deletedCount++;
            }
        });

        // Delete files only after the DB transaction has committed successfully.
        $this->deleteFiles($filesToDelete);

        $message = $deletedCount . ' user(s) deleted.';
        if (!empty($officialOwnerIds)) {
            $skippedNames = User::whereIn('id', $officialOwnerIds)->pluck('name')->implode(', ');
            $message .= ' Skipped ' . count($officialOwnerIds)
                . ' user(s) who own Official plans (reassign Official first): ' . $skippedNames . '.';
        }

        return redirect()->route('admin.index')->with('success', $message);
    }

    /**
     * Resend the email verification notification for a specific user.
     *
     * Called by the "Verify" AJAX button in the admin users table.
     * Returns JSON so Alpine.js can update the button label without a page reload.
     * Route is throttled at 6/minute (defined in routes/web.php).
     */
    public function sendVerification(User $user): JsonResponse
    {
        if (! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }

        return response()->json(['sent' => true]);
    }

    /**
     * Change a user's password directly (admin action).
     *
     * Validates, hashes, and saves the new password, then emails the user
     * a notification so they are aware of the change. Returns JSON so
     * Alpine.js can update the inline form without a page reload.
     * Route is throttled at 6/minute (defined in routes/web.php).
     */
    public function changePassword(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user->update(['password' => Hash::make($data['password'])]);

        $user->notify(new \App\Notifications\PasswordChangedByAdminNotification());

        return response()->json(['changed' => true]);
    }

    /**
     * Set a lesson plan as the Official version for its (class_name, grade, lesson_day).
     *
     * Clears is_official on all other plans in the same class/grade/day, then marks
     * the target plan. This is an admin-only action.
     */
    public function setOfficial(LessonPlan $lessonPlan): RedirectResponse
    {
        // Wrap in a transaction with a pessimistic lock so two simultaneous
        // setOfficial requests for the same class/grade/day cannot both commit,
        // preventing a window where zero or two plans are marked official.
        DB::transaction(function () use ($lessonPlan) {
            // Acquire a write lock on all rows for this class/grade/day before updating.
            DB::table('lesson_plans')
                ->where('class_name', $lessonPlan->class_name)
                ->where('grade',      $lessonPlan->grade)
                ->where('lesson_day', $lessonPlan->lesson_day)
                ->lockForUpdate()
                ->get(); // fetch to acquire the lock; result is not used

            // Clear existing official designation for this class/grade/day combination.
            DB::table('lesson_plans')
                ->where('class_name', $lessonPlan->class_name)
                ->where('grade',      $lessonPlan->grade)
                ->where('lesson_day', $lessonPlan->lesson_day)
                ->update(['is_official' => false]);

            // Mark the chosen plan as official.
            DB::table('lesson_plans')
                ->where('id', $lessonPlan->id)
                ->update(['is_official' => true]);
        });

        return back()->with('success',
            "Version {$lessonPlan->semantic_version} of {$lessonPlan->class_name} Lesson {$lessonPlan->lesson_day} is now the Official version.");
    }

    /**
     * Relocate a lesson plan to a new class / grade / lesson_day.
     *
     * Updates the DB fields and renames the file on disk to reflect the new
     * canonical name. If the target slot already has plans (conflict), returns
     * HTTP 409 with conflict details so the client can prompt the user.
     *
     * conflict_resolution:
     *   'overwrite' — proceed despite the conflict (plan joins the target family).
     *   'suffix'    — append .1/.2/… to file_name to avoid filename collision.
     */
    public function relocatePlan(Request $request, LessonPlan $lessonPlan): JsonResponse
    {
        $data = $request->validate([
            'class_name'          => ['nullable', 'string', 'max:100'],
            'grade'               => ['nullable', 'integer', 'between:10,12'],
            'lesson_day'          => ['nullable', 'integer', 'min:1', 'max:999'],
            'conflict_resolution' => ['nullable', 'string', 'in:overwrite,suffix'],
        ]);

        $newClass  = $data['class_name'] ?? $lessonPlan->class_name;
        $newGrade  = isset($data['grade'])      ? (int) $data['grade']      : $lessonPlan->grade;
        $newDay    = isset($data['lesson_day']) ? (int) $data['lesson_day'] : $lessonPlan->lesson_day;

        // Nothing actually changed — no-op.
        if ($newClass === $lessonPlan->class_name
            && $newGrade  === $lessonPlan->grade
            && $newDay    === $lessonPlan->lesson_day) {
            return response()->json(['message' => 'No changes.'], 200);
        }

        // Conflict check: another plan already exists in the target slot.
        $resolution = $data['conflict_resolution'] ?? null;
        $conflictExists = LessonPlan::where('class_name', $newClass)
            ->where('grade', $newGrade)
            ->where('lesson_day', $newDay)
            ->where('id', '!=', $lessonPlan->id)
            ->exists();

        if ($conflictExists && ! $resolution) {
            return response()->json(['conflict' => true], 409);
        }

        // Compute the new canonical name.
        $authorName = $lessonPlan->author?->name ?? 'Unknown';
        $ts         = Carbon::parse($lessonPlan->created_at, 'UTC');
        $newName    = LessonPlan::generateCanonicalName(
            $newClass, $newGrade, $newDay, $authorName, $ts,
            [$lessonPlan->version_major, $lessonPlan->version_minor, $lessonPlan->version_patch]
        );

        // If 'suffix' resolution, append .1/.2/… until the name is unique.
        if ($resolution === 'suffix') {
            $base   = $newName;
            $suffix = 1;
            while (LessonPlan::where('name', $newName)->where('id', '!=', $lessonPlan->id)->exists()) {
                $newName = $base . '.' . $suffix++;
            }
        }

        // Build new file_name (adds original extension).
        $ext         = $lessonPlan->file_name
            ? strtolower(pathinfo($lessonPlan->file_name, PATHINFO_EXTENSION))
            : null;
        $newFileName = $ext ? "{$newName}.{$ext}" : $newName;
        $newFilePath = $lessonPlan->file_path ? "lessons/{$newFileName}" : null;

        // Rename the file on disk (file-first: if disk move fails, the DB update below
        // is not reached and the record continues pointing to the original path).
        if ($lessonPlan->file_path && $newFilePath && $lessonPlan->file_path !== $newFilePath) {
            $this->resolveFileDisk($lessonPlan->file_path)?->move($lessonPlan->file_path, $newFilePath);
        }

        $lessonPlan->update([
            'class_name' => $newClass,
            'grade'      => $newGrade,
            'lesson_day' => $newDay,
            'name'       => $newName,
            'file_name'  => $newFileName,
            'file_path'  => $newFilePath ?? $lessonPlan->file_path,
        ]);

        return response()->json([
            'success'   => true,
            'class_name' => $lessonPlan->class_name,
            'grade'      => $lessonPlan->grade,
            'lesson_day' => $lessonPlan->lesson_day,
            'file_name'  => $lessonPlan->file_name,
        ]);
    }

    /**
     * AJAX endpoint: compute a diff between two arbitrary lesson plan files.
     *
     * Returns JSON suitable for Alpine.js rendering in the admin compare panel.
     * plan_a is treated as "current"; plan_b as "baseline".
     */
    public function comparePlans(Request $request): JsonResponse
    {
        $data = $request->validate([
            'plan_a' => ['required', 'integer', 'exists:lesson_plans,id'],
            'plan_b' => ['required', 'integer', 'exists:lesson_plans,id'],
        ]);

        $planA = LessonPlan::with('author')->find($data['plan_a']);
        $planB = LessonPlan::with('author')->find($data['plan_b']);

        [$aOk, $aLines, $aErr] = $this->readPlanLinesForDiff($planA);
        [$bOk, $bLines, $bErr] = $this->readPlanLinesForDiff($planB);

        if (! $aOk || ! $bOk) {
            return response()->json(['warning' => $aErr ?: $bErr]);
        }

        if (count($aLines) > 500 || count($bLines) > 500) {
            return response()->json(['warning' => 'Files are too large for inline diff (limit: 500 lines).']);
        }

        // plan_b is baseline (old), plan_a is current (new).
        $diffOps     = $this->buildLineDiffOperations($bLines, $aLines);
        $diffSummary = $this->buildDiffSummary($diffOps);
        $sideBySide  = $this->buildSideBySideDiff($diffOps);

        return response()->json([
            'planA'       => [
                'id'      => $planA->id,
                'label'   => "v{$planA->semantic_version} — {$planA->class_name} G{$planA->grade} D{$planA->lesson_day}",
                'author'  => $planA->author?->name ?? 'Anonymous',
            ],
            'planB'       => [
                'id'      => $planB->id,
                'label'   => "v{$planB->semantic_version} — {$planB->class_name} G{$planB->grade} D{$planB->lesson_day}",
                'author'  => $planB->author?->name ?? 'Anonymous',
            ],
            'diffOps'     => $diffOps,
            'diffSummary' => $diffSummary,
            'sideBySide'  => $sideBySide,
            'warning'     => null,
        ]);
    }

    /** Delete the stored file for a lesson plan, if one exists. */
    private function deletePlanFile(LessonPlan $plan): void
    {
        if ($plan->file_path) {
            $this->resolveFileDisk($plan->file_path)?->delete($plan->file_path);
        }
    }

    /** Delete a batch of file paths (used after DB transactions commit). */
    private function deleteFiles(array $paths): void
    {
        foreach ($paths as $path) {
            $this->resolveFileDisk($path)?->delete($path);
        }
    }
}
