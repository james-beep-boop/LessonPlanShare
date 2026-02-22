<?php

namespace App\Http\Controllers;

use App\Models\LessonPlan;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Main dashboard: browsable, searchable, sortable table of all lesson plans.
     * Shows the latest version of each plan family by default.
     * Toggle "show_all_versions" to see every version.
     */
    public function index(Request $request)
    {
        $query = LessonPlan::with('author');

        // Filter: show only latest versions unless user wants all
        if (!$request->boolean('show_all_versions')) {
            $query->latestVersions();
        }

        // Search across canonical name, class_name, description, and author name
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

        // Filter by class name
        if ($className = $request->input('class_name')) {
            $query->where('class_name', $className);
        }

        // Sorting (validate direction to prevent SQL errors from bad input)
        $sortField = $request->input('sort', 'updated_at');
        $sortOrder = in_array(strtolower($request->input('order', 'desc')), ['asc', 'desc'])
            ? strtolower($request->input('order', 'desc'))
            : 'desc';

        $allowedSorts = [
            'name', 'class_name', 'lesson_day', 'version_number',
            'vote_score', 'updated_at', 'created_at',
        ];

        if ($sortField === 'author') {
            // Sort by author name via a join
            $query->join('users', 'lesson_plans.author_id', '=', 'users.id')
                  ->orderBy('users.name', $sortOrder)
                  ->select('lesson_plans.*');
        } elseif (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortOrder);
        } else {
            $query->orderBy('updated_at', 'desc');
        }

        $plans = $query->paginate(10)->withQueryString();

        // Get distinct class names for the filter dropdown
        $classNames = LessonPlan::select('class_name')
            ->distinct()
            ->orderBy('class_name')
            ->pluck('class_name');

        return view('dashboard', compact('plans', 'classNames', 'sortField', 'sortOrder'));
    }
}
