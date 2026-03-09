<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\LessonPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * My Contributions page — shows only the authenticated user's own lesson plans.
 *
 * Unlike the main dashboard (DashboardController) this page:
 * - Always filters to Auth::id() — no my_plans_only toggle needed
 * - Includes a Delete column (author may delete their own non-official plans)
 * - Omits the 4-stat counter boxes
 * - Has no official_only / latest_only / favorites_only / my_plans_only checkboxes
 */
class MyContributionsController extends Controller
{
    public function index(Request $request)
    {
        $query = LessonPlan::query()
            ->leftJoin('users', 'users.id', '=', 'lesson_plans.author_id')
            ->select('lesson_plans.*', 'users.name as author_name')
            ->where('lesson_plans.author_id', Auth::id());

        // Free-text search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('lesson_plans.name', 'like', "%{$search}%")
                  ->orWhere('lesson_plans.class_name', 'like', "%{$search}%")
                  ->orWhere('lesson_plans.description', 'like', "%{$search}%")
                  ->orWhereRaw("CONCAT(lesson_plans.version_major, '.', lesson_plans.version_minor, '.', lesson_plans.version_patch) LIKE ?", ["%{$search}%"])
                  ->orWhereRaw("CAST(lesson_plans.lesson_day AS CHAR) LIKE ?", ["%{$search}%"]);
            });
        }

        // Filter by class name (exact match from dropdown)
        if ($className = $request->input('class_name')) {
            $query->where('lesson_plans.class_name', $className);
        }

        // Sorting — whitelist matches columns visible in the My Contributions table
        $sortField = $request->input('sort', 'class_name');
        $rawOrder  = strtolower($request->input('order', 'asc'));
        $sortOrder = in_array($rawOrder, ['asc', 'desc']) ? $rawOrder : 'asc';

        $allowedSorts = [
            'is_official', 'class_name', 'grade', 'lesson_day', 'description',
            'semantic_version', 'vote_score', 'updated_at', 'is_favorited',
        ];

        if (in_array($sortField, $allowedSorts)) {
            if ($sortField === 'semantic_version') {
                $query->orderBy('lesson_plans.version_major', $sortOrder)
                      ->orderBy('lesson_plans.version_minor', $sortOrder)
                      ->orderBy('lesson_plans.version_patch', $sortOrder);
            } elseif ($sortField === 'is_favorited') {
                $query->leftJoin('favorites as fav_sort', function ($join) {
                    $join->on('fav_sort.lesson_plan_id', '=', 'lesson_plans.id')
                         ->where('fav_sort.user_id', '=', Auth::id());
                })->orderByRaw('ISNULL(fav_sort.id) ' . ($sortOrder === 'asc' ? 'ASC' : 'DESC'));
            } else {
                $query->orderBy('lesson_plans.' . $sortField, $sortOrder);
            }
        } else {
            $query->orderBy('lesson_plans.updated_at', 'desc');
        }

        $plans = $query->paginate(20)->withQueryString();

        // Favorited plan IDs for pre-populating favorite stars
        $planIds      = $plans->pluck('id');
        $favoritedIds = Favorite::whereIn('lesson_plan_id', $planIds)
            ->where('user_id', Auth::id())
            ->pluck('lesson_plan_id')
            ->toArray();

        // Class names for the filter dropdown (classes this user has uploaded to)
        $classNames = LessonPlan::where('author_id', Auth::id())
            ->select('class_name')
            ->distinct()
            ->orderBy('class_name')
            ->pluck('class_name');

        return view('my-contributions', compact(
            'plans', 'classNames', 'sortField', 'sortOrder', 'favoritedIds'
        ));
    }
}
