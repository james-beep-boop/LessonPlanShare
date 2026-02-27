<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\LessonPlan;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Handles favoriting / unfavoriting lesson plans.
 * Only authenticated + verified users can favorite (enforced by route middleware).
 */
class FavoriteController extends Controller
{
    /**
     * Toggle the favorite state for the authenticated user on a given plan.
     * Always returns JSON: { favorited: bool }
     */
    public function toggle(LessonPlan $lessonPlan): JsonResponse
    {
        $existing = Favorite::where('lesson_plan_id', $lessonPlan->id)
            ->where('user_id', Auth::id())
            ->first();

        if ($existing) {
            $existing->delete();
            return response()->json(['favorited' => false]);
        }

        // Wrap in try/catch: a rapid double-click can send two concurrent requests that both
        // pass the first() check above (returning null) and both attempt create(). The unique
        // index on [user_id, lesson_plan_id] will reject the second with a QueryException.
        // We treat that as idempotent — the row was already created, so return favorited: true.
        try {
            Favorite::create([
                'user_id'        => Auth::id(),
                'lesson_plan_id' => $lessonPlan->id,
            ]);
        } catch (QueryException) {
            // Unique constraint violation — concurrent request beat us to it; already favorited.
        }

        return response()->json(['favorited' => true]);
    }
}
