<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\LessonPlan;
use App\Models\LessonPlanView;
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

        // Filter: show only the authenticated user's favorited plans.
        if ($request->boolean('favorites_only') && Auth::check() && Auth::user()->hasVerifiedEmail()) {
            $favoritedPlanIds = Favorite::where('user_id', Auth::id())->pluck('lesson_plan_id');
            $query->whereIn('lesson_plans.id', $favoritedPlanIds);
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
        $sortField = $request->input('sort', 'class_name');
        $sortOrder = in_array(strtolower($request->input('order', 'asc')), ['asc', 'desc'])
            ? strtolower($request->input('order', 'asc'))
            : 'asc';

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

        // Paginate at 20 per page. withQueryString() threads search/sort/filter params
        // through pagination links so they survive page navigation.
        $plans = $query->paginate(20)->withQueryString();

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
        // Total plans: every version counts as one (not just latest)
        $totalPlanCount = LessonPlan::count();

        // Contributors: distinct authors who have uploaded at least one plan
        $contributorCount = LessonPlan::distinct('author_id')->count('author_id');

        // Top-rated plan: highest net vote_score (upvotes − downvotes). Used in counter box.
        $topRatedPlan = LessonPlan::with('author')
            ->where('vote_score', '>', 0)
            ->orderByDesc('vote_score')
            ->orderByDesc('updated_at')
            ->first();

        // Top contributor: the author with the most uploads
        $topContributor = LessonPlan::select('author_id', DB::raw('COUNT(*) as upload_count'))
            ->with('author')
            ->groupBy('author_id')
            ->orderByDesc('upload_count')
            ->first();

        return response()
            ->view('dashboard', compact(
                'plans', 'classNames', 'sortField', 'sortOrder',
                'totalPlanCount', 'contributorCount', 'topRatedPlan', 'topContributor',
                'userVotes', 'viewedIds', 'favoritedIds'
            ))
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, private')
            ->header('Pragma', 'no-cache');
    }

}
