<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\LessonPlan;
use App\Models\LessonPlanView;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Dashboard controller — the main public-facing page of the application.
 *
 * This is the route for "/" (the root URL). It shows a searchable, sortable,
 * paginated table of lesson plans. By default it shows ALL versions; a checkbox
 * lets the user restrict to the latest version of each plan family.
 *
 * This route is PUBLIC (no auth required) so visitors can browse and
 * download lesson plans without creating an account. Uploading, voting,
 * and other actions require authentication via the 'auth' + 'verified'
 * middleware defined in routes/web.php.
 */
class DashboardController extends Controller
{
    /**
     * Display the main dashboard with all lesson plans.
     *
     * Query parameters (all optional):
     * - search: free-text search across name, class, description, author
     * - class_name: filter to a specific class (e.g., "English")
     * - latest_only: if truthy, show only the latest version of each plan family
     * - sort: column to sort by (name, class_name, lesson_day, etc.)
     * - order: 'asc' or 'desc' (validated server-side; bad values default to 'desc')
     *
     * Pagination: 10 rows per page (matching the design spec for
     * "first 10 rows sorted by most recent").
     */
    public function index(Request $request)
    {
        // LEFT JOIN users so we can display and sort by author name.
        // We select lesson_plans.* explicitly to avoid column ambiguity
        // (both tables have id, name, created_at, updated_at).
        $query = LessonPlan::query()
            ->leftJoin('users', 'users.id', '=', 'lesson_plans.author_id')
            ->select('lesson_plans.*', 'users.name as author_name');

        // Filter: show all versions by default. When latest_only=1 is passed,
        // restrict to one row per family (the highest-id version).
        if ($request->boolean('latest_only')) {
            $query->latestVersions();
        }

        // Free-text search across multiple fields including author name.
        // Uses LIKE with wildcards — adequate for the expected data volume.
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                // Prefix lesson_plans columns to avoid ambiguity with the users JOIN.
                // users.name is searched directly (replaces the old orWhereHas subquery).
                $q->where('lesson_plans.name', 'like', "%{$search}%")
                  ->orWhere('lesson_plans.class_name', 'like', "%{$search}%")
                  ->orWhere('lesson_plans.description', 'like', "%{$search}%")
                  ->orWhere('users.name', 'like', "%{$search}%");
            });
        }

        // Filter by class name (exact match from dropdown)
        if ($className = $request->input('class_name')) {
            $query->where('lesson_plans.class_name', $className);
        }

        // ── Sorting ──
        // Validate sort direction to prevent SQL injection / 500 errors.
        // Only 'asc' and 'desc' are accepted; anything else defaults to 'desc'.
        $sortField = $request->input('sort', 'updated_at');
        $sortOrder = in_array(strtolower($request->input('order', 'desc')), ['asc', 'desc'])
            ? strtolower($request->input('order', 'desc'))
            : 'desc';

        // Whitelist of allowed sort columns — must match visible dashboard columns.
        // author_name sorts by users.name via the LEFT JOIN.
        // All other columns are prefixed with lesson_plans. to avoid JOIN ambiguity.
        $allowedSorts = [
            'class_name', 'lesson_day', 'author_name',
            'semantic_version', 'vote_score', 'updated_at',
        ];

        if (in_array($sortField, $allowedSorts)) {
            if ($sortField === 'author_name') {
                $query->orderBy('users.name', $sortOrder);
            } elseif ($sortField === 'semantic_version') {
                // Semantic version is stored in three integer columns; sort numerically.
                $query->orderBy('lesson_plans.version_major', $sortOrder)
                      ->orderBy('lesson_plans.version_minor', $sortOrder)
                      ->orderBy('lesson_plans.version_patch', $sortOrder);
            } else {
                $query->orderBy('lesson_plans.' . $sortField, $sortOrder);
            }
        } else {
            // Unknown sort field — fall back to most-recent
            $query->orderBy('lesson_plans.updated_at', 'desc');
        }

        // Paginate at 10 per page; withQueryString() preserves search/sort params
        $plans = $query->paginate(10)->withQueryString();

        // For logged-in users: load their existing votes, viewed plan IDs, and favorites.
        // Used to show interactive vote buttons and pre-populate favorite checkboxes.
        $userVotes    = [];
        $viewedIds    = [];
        $favoritedIds = [];
        if (Auth::check()) {
            $planIds      = $plans->pluck('id');
            $userVotes    = Vote::whereIn('lesson_plan_id', $planIds)
                ->where('user_id', Auth::id())
                ->pluck('value', 'lesson_plan_id')
                ->toArray();
            $viewedIds    = LessonPlanView::whereIn('lesson_plan_id', $planIds)
                ->where('user_id', Auth::id())
                ->pluck('lesson_plan_id')
                ->toArray();
            $favoritedIds = Favorite::whereIn('lesson_plan_id', $planIds)
                ->where('user_id', Auth::id())
                ->pluck('lesson_plan_id')
                ->toArray();
        }

        // Get distinct class names that actually have plans, for the filter dropdown.
        // (This is separate from LessonPlanController::CLASS_NAMES, which controls
        // what can be *uploaded*. The dashboard filter shows what *exists*.)
        $classNames = LessonPlan::select('class_name')
            ->distinct()
            ->orderBy('class_name')
            ->pluck('class_name');

        // ── Dashboard counters ──
        // Unique classes: how many distinct class names have at least one plan
        $uniqueClassCount = LessonPlan::distinct('class_name')->count('class_name');

        // Total plans: every version counts as one (not just latest)
        $totalPlanCount = LessonPlan::count();

        // Registered users: total number of accounts (verified or not)
        $userCount = User::count();

        // Favorite plan: the single plan with the highest net vote score
        // (upvotes minus downvotes). Ties broken by most recent. Eager-load author.
        $favoritePlan = LessonPlan::with('author')
            ->orderByDesc('vote_score')
            ->orderByDesc('updated_at')
            ->first();

        return view('dashboard', compact(
            'plans', 'classNames', 'sortField', 'sortOrder',
            'uniqueClassCount', 'totalPlanCount', 'favoritePlan',
            'userVotes', 'viewedIds', 'favoritedIds', 'userCount'
        ));
    }

    /**
     * Display the Stats page with detailed archive statistics.
     *
     * Public route — anyone can view statistics, no auth required.
     * Computes aggregate data about the lesson plan archive including:
     * - Total plans and unique classes (same as dashboard counters)
     * - Total contributors (distinct authors)
     * - Plans per class (bar-chart-style breakdown)
     * - Top 5 highest-rated plans
     * - Top 5 most prolific contributors
     * - Most revised plan family (most versions)
     */
    public function stats()
    {
        // ── Basic counters ──
        $uniqueClassCount = LessonPlan::distinct('class_name')->count('class_name');
        $totalPlanCount   = LessonPlan::count();
        $contributorCount = LessonPlan::distinct('author_id')->count('author_id');

        // ── Plans per class ──
        // Returns a collection of objects with class_name and plan_count
        $plansPerClass = LessonPlan::select('class_name', DB::raw('COUNT(*) as plan_count'))
            ->groupBy('class_name')
            ->orderByDesc('plan_count')
            ->get();

        // ── Top 5 highest-rated plans ──
        $topRated = LessonPlan::with('author')
            ->where('vote_score', '>', 0)
            ->orderByDesc('vote_score')
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get();

        // ── Top 5 most prolific contributors ──
        // Counts how many plans each author has uploaded (all versions)
        $topContributors = LessonPlan::select('author_id', DB::raw('COUNT(*) as upload_count'))
            ->with('author')
            ->groupBy('author_id')
            ->orderByDesc('upload_count')
            ->limit(5)
            ->get();

        // ── Most revised plan family ──
        // The root plan whose family has the most versions.
        // Alias is 'family_root_id' (not 'root_id') to avoid triggering the
        // model's getRootIdAttribute() accessor, which would throw a TypeError
        // because this partial SELECT omits the 'id' and 'original_id' columns.
        // Must group by the full expression (not the alias) for MySQL ONLY_FULL_GROUP_BY.
        $mostRevised = LessonPlan::select(
                DB::raw('COALESCE(original_id, id) as family_root_id'),
                DB::raw('COUNT(*) as version_count')
            )
            ->groupBy(DB::raw('COALESCE(original_id, id)'))
            ->orderByDesc('version_count')
            ->first();

        $mostRevisedPlan = null;
        if ($mostRevised && $mostRevised->version_count > 1) {
            $mostRevisedPlan = LessonPlan::with('author')->find($mostRevised->family_root_id);
            if ($mostRevisedPlan) {
                $mostRevisedPlan->family_version_count = $mostRevised->version_count;
            }
        }

        return view('stats', compact(
            'uniqueClassCount', 'totalPlanCount', 'contributorCount',
            'plansPerClass', 'topRated', 'topContributors', 'mostRevisedPlan'
        ));
    }

    /**
     * Resend the email verification notification for a specific user.
     * Used by the "Verify" button in the debug registered-users table.
     * Returns JSON so the button can update its label via Alpine.js.
     */
    public function sendVerification(User $user): \Illuminate\Http\JsonResponse
    {
        if (! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }

        return response()->json(['sent' => true]);
    }
}
