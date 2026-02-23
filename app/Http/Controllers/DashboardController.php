<?php

namespace App\Http\Controllers;

use App\Models\LessonPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Dashboard controller — the main public-facing page of the application.
 *
 * This is the route for "/" (the root URL). It shows a searchable, sortable,
 * paginated table of lesson plans. By default it shows only the latest
 * version of each plan family; a checkbox lets the user see all versions.
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
     * - show_all_versions: if truthy, show every version (not just latest)
     * - sort: column to sort by (name, class_name, lesson_day, etc.)
     * - order: 'asc' or 'desc' (validated server-side; bad values default to 'desc')
     *
     * Pagination: 10 rows per page (matching the design spec for
     * "first 10 rows sorted by most recent").
     */
    public function index(Request $request)
    {
        $query = LessonPlan::with('author');

        // Filter: show only the latest version of each plan family by default.
        // This uses a subquery that groups by COALESCE(original_id, id) and
        // selects MAX(id) from each group.
        if (!$request->boolean('show_all_versions')) {
            $query->latestVersions();
        }

        // Free-text search across multiple fields including author name.
        // Uses LIKE with wildcards — adequate for the expected data volume.
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('class_name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('author', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by class name (exact match from dropdown)
        if ($className = $request->input('class_name')) {
            $query->where('class_name', $className);
        }

        // ── Sorting ──
        // Validate sort direction to prevent SQL injection / 500 errors.
        // Only 'asc' and 'desc' are accepted; anything else defaults to 'desc'.
        $sortField = $request->input('sort', 'updated_at');
        $sortOrder = in_array(strtolower($request->input('order', 'desc')), ['asc', 'desc'])
            ? strtolower($request->input('order', 'desc'))
            : 'desc';

        // Whitelist of allowed sort columns to prevent arbitrary column access
        $allowedSorts = [
            'name', 'class_name', 'lesson_day', 'version_number',
            'vote_score', 'updated_at', 'created_at',
        ];

        if ($sortField === 'author') {
            // Sort by author name requires a JOIN to the users table
            $query->join('users', 'lesson_plans.author_id', '=', 'users.id')
                  ->orderBy('users.name', $sortOrder)
                  ->select('lesson_plans.*');  // avoid column name collisions
        } elseif (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortOrder);
        } else {
            // Unknown sort field — fall back to most-recent
            $query->orderBy('updated_at', 'desc');
        }

        // Paginate at 10 per page; withQueryString() preserves search/sort params
        $plans = $query->paginate(10)->withQueryString();

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

        // Favorite plan: the single plan with the highest net vote score
        // (upvotes minus downvotes). Ties broken by most recent. Eager-load author.
        $favoritePlan = LessonPlan::with('author')
            ->orderByDesc('vote_score')
            ->orderByDesc('updated_at')
            ->first();

        return view('dashboard', compact(
            'plans', 'classNames', 'sortField', 'sortOrder',
            'uniqueClassCount', 'totalPlanCount', 'favoritePlan'
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
        // The root plan whose family has the most versions
        $mostRevised = LessonPlan::select(
                DB::raw('COALESCE(original_id, id) as root_id'),
                DB::raw('COUNT(*) as version_count')
            )
            ->groupBy('root_id')
            ->orderByDesc('version_count')
            ->first();

        $mostRevisedPlan = null;
        if ($mostRevised && $mostRevised->version_count > 1) {
            $mostRevisedPlan = LessonPlan::with('author')->find($mostRevised->root_id);
            if ($mostRevisedPlan) {
                $mostRevisedPlan->family_version_count = $mostRevised->version_count;
            }
        }

        return view('stats', compact(
            'uniqueClassCount', 'totalPlanCount', 'contributorCount',
            'plansPerClass', 'topRated', 'topContributors', 'mostRevisedPlan'
        ));
    }
}
