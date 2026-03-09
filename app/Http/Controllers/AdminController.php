<?php

namespace App\Http\Controllers;

use App\Models\LessonPlan;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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
    /** The email address of the super-administrator (only account that can revoke admin). */
    private const SUPER_ADMIN_EMAIL = 'priority2@protonmail.ch';
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
            'allPlansCumulative', 'officialCumulative', 'downloadCumulative'
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

        // Revoking admin is restricted to the super-admin
        if ($user->is_admin && Auth::user()->email !== self::SUPER_ADMIN_EMAIL) {
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
     * All of the user's plans are locked for update (not just official ones) so
     * a concurrent setOfficial() cannot promote a non-official plan between the
     * check and the delete.  Files are deleted after the DB commit, not inside
     * the transaction, so a commit failure cannot leave orphaned DB rows with
     * already-deleted files on disk.
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
            // Lock ALL of this user's plans (not just official ones) so a concurrent
            // setOfficial() cannot promote a non-official plan between our check and
            // the delete. bulkDestroyUsers() uses the same broad-lock pattern.
            $plans = LessonPlan::where('author_id', $user->id)->lockForUpdate()->get();

            $officialCount = $plans->where('is_official', true)->count();

            if ($officialCount > 0) {
                $blocked = true;
                return; // abort the closure; transaction rolls back (no writes happened)
            }

            // Collect file paths before deleting rows. Files are removed after the
            // transaction commits; deleting them inside the transaction risks losing
            // files on disk if the subsequent DB commit fails.
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
     * The official-ownership check and each user deletion run inside a single
     * transaction with a pessimistic lock to close the same race window as
     * destroyUser().
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
            // Lock all plans for the selected users so no concurrent setOfficial()
            // can promote a plan between our check and the delete. Group by author_id
            // so we can reuse this collection instead of issuing per-user queries below.
            $allPlans = LessonPlan::whereIn('author_id', $ids)->lockForUpdate()->get()->groupBy('author_id');

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

    /** Delete the stored file for a lesson plan, if one exists. */
    private function deletePlanFile(LessonPlan $plan): void
    {
        if ($plan->file_path) {
            Storage::disk('public')->delete($plan->file_path);
        }
    }

    /** Delete a batch of file paths from the public disk (used after DB transactions commit). */
    private function deleteFiles(array $paths): void
    {
        if ($paths) {
            Storage::disk('public')->delete($paths);
        }
    }
}
