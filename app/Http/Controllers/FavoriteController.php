<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\LessonPlan;
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

        Favorite::create([
            'user_id'        => Auth::id(),
            'lesson_plan_id' => $lessonPlan->id,
        ]);

        return response()->json(['favorited' => true]);
    }
}
