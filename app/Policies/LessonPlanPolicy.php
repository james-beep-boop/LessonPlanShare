<?php

namespace App\Policies;

use App\Models\LessonPlan;
use App\Models\User;

/**
 * Authorization policy for lesson plan actions.
 *
 * Auto-discovered by Laravel (App\Models\LessonPlan → App\Policies\LessonPlanPolicy).
 * No manual registration in AuthServiceProvider is needed.
 *
 * Usage in controller: $this->authorize('delete', $lessonPlan)
 *
 * The before() hook gives admins a blanket pass on all policy checks,
 * which keeps AdminController::destroyPlan() from needing its own policy.
 */
class LessonPlanPolicy
{
    /**
     * Admins bypass all policy checks unconditionally.
     *
     * Returning true short-circuits the specific ability method.
     * Returning null falls through to the specific method (non-admin users).
     */
    public function before(User $user, string $ability): bool|null
    {
        return $user->is_admin ? true : null;
    }

    /**
     * Only the plan's author may delete it via the normal delete route.
     *
     * Admins are already handled by before() above.
     * The version-tree guard (no descendants) is enforced separately in the controller.
     */
    public function delete(User $user, LessonPlan $plan): bool
    {
        return $user->id === $plan->author_id;
    }
}
