<?php

namespace App\Http\Controllers;

use App\Models\LessonPlan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Admin panel controller.
 *
 * All routes here require auth + verified + is_admin (AdminMiddleware).
 * Admins can delete any lesson plan or any user account, bypassing
 * the normal author-only restrictions.
 */
class AdminController extends Controller
{
    /** Display the admin panel. */
    public function index()
    {
        $plans = LessonPlan::query()
            ->leftJoin('users', 'users.id', '=', 'lesson_plans.author_id')
            ->select('lesson_plans.*', 'users.name as author_name')
            ->orderByDesc('lesson_plans.updated_at')
            ->paginate(50, ['*'], 'plans_page');

        $users = User::orderBy('created_at')->paginate(50, ['*'], 'users_page');

        return view('admin.index', compact('plans', 'users'));
    }

    /** Delete a single lesson plan (admin bypasses author check). */
    public function destroyPlan(LessonPlan $lessonPlan)
    {
        // Remove the file from storage
        if ($lessonPlan->file_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($lessonPlan->file_path);
        }

        $lessonPlan->delete();

        return redirect()->route('admin.index')->with('success', 'Lesson plan deleted.');
    }

    /** Bulk-delete multiple lesson plans by ID array. */
    public function bulkDestroyPlans(Request $request)
    {
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return redirect()->route('admin.index')->with('error', 'No plans selected.');
        }

        $plans = LessonPlan::whereIn('id', $ids)->get();

        foreach ($plans as $plan) {
            if ($plan->file_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($plan->file_path);
            }
            $plan->delete();
        }

        return redirect()->route('admin.index')
            ->with('success', count($plans) . ' lesson plan(s) deleted.');
    }

    /** Delete a single user account. */
    public function destroyUser(User $user)
    {
        // Prevent self-deletion
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.index')->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('admin.index')->with('success', 'User deleted.');
    }

    /** Bulk-delete multiple user accounts by ID array. */
    public function bulkDestroyUsers(Request $request)
    {
        $ids = array_filter(
            $request->input('user_ids', []),
            fn ($id) => (int) $id !== auth()->id()  // never delete self
        );

        if (empty($ids)) {
            return redirect()->route('admin.index')->with('error', 'No users selected (or only yourself).');
        }

        $count = User::whereIn('id', $ids)->delete();

        return redirect()->route('admin.index')
            ->with('success', $count . ' user(s) deleted.');
    }
}
