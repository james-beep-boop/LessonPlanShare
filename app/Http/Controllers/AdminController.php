<?php

namespace App\Http\Controllers;

use App\Models\LessonPlan;
use App\Models\User;
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
        $data = $request->validate([
            'ids'   => ['required', 'array'],
            'ids.*' => ['integer', 'exists:lesson_plans,id'],
        ]);
        $ids = $data['ids'];

        $plans = LessonPlan::whereIn('id', $ids)->get();

        foreach ($plans as $plan) {
            if ($plan->file_path) {
                Storage::disk('public')->delete($plan->file_path);
            }
            $plan->delete();
        }

        return redirect()->route('admin.index')
            ->with('success', count($plans) . ' lesson plan(s) deleted.');
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

    /** Delete a single user account and all their uploaded lesson plan files. */
    public function destroyUser(User $user)
    {
        // Prevent self-deletion
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.index')->with('error', 'You cannot delete your own account.');
        }

        // Remove every lesson plan file this user uploaded before deleting the DB rows.
        // The foreign-key CASCADE would remove lesson_plan rows automatically, but it
        // would leave orphaned files on disk — so we clean up manually first.
        $plans = LessonPlan::where('author_id', $user->id)->get();
        foreach ($plans as $plan) {
            if ($plan->file_path) {
                Storage::disk('public')->delete($plan->file_path);
            }
        }

        $user->delete();

        return redirect()->route('admin.index')->with('success', 'User deleted.');
    }

    /** Bulk-delete multiple user accounts and all their uploaded lesson plan files. */
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

        // Load users individually so we can clean up their files before deleting.
        // Using a mass-delete (whereIn->delete()) would skip file cleanup.
        $users = User::whereIn('id', $ids)->get();
        foreach ($users as $user) {
            $plans = LessonPlan::where('author_id', $user->id)->get();
            foreach ($plans as $plan) {
                if ($plan->file_path) {
                    Storage::disk('public')->delete($plan->file_path);
                }
            }
            $user->delete();
        }

        return redirect()->route('admin.index')
            ->with('success', count($users) . ' user(s) deleted.');
    }
}
