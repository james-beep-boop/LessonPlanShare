<?php

namespace App\Http\Controllers;

use App\Models\LessonPlan;
use App\Models\LessonPlanView;
use App\Models\Vote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Handles upvote/downvote actions on lesson plan versions.
 *
 * Voting behavior:
 * - Each user gets exactly one vote per lesson plan version.
 * - Voting the same direction again REMOVES the vote (toggle off).
 * - Voting the opposite direction SWITCHES the vote.
 * - Authors cannot vote on their own plans.
 * - The cached vote_score on the lesson plan is recalculated after each action.
 *
 * Only authenticated + verified users can vote (enforced by route middleware).
 */
class VoteController extends Controller
{
    /**
     * Cast an upvote (+1) or downvote (-1) on a lesson plan version.
     *
     * @param  Request     $request     Must contain 'value' of -1 or 1.
     * @param  LessonPlan  $lessonPlan  The plan version being voted on.
     */
    public function store(Request $request, LessonPlan $lessonPlan)
    {
        $data = $request->validate([
            'value' => 'required|integer|in:-1,1',
        ]);

        // Guard: prevent authors from voting on their own plans
        if ($lessonPlan->author_id === Auth::id()) {
            return $request->expectsJson()
                ? response()->json(['message' => 'You cannot vote on your own lesson plan.'], 403)
                : back()->with('error', 'You cannot vote on your own lesson plan.');
        }

        // Guard: must have viewed the plan before voting (UI also enforces this;
        // this server-side check prevents API-level bypasses)
        if (! LessonPlanView::where('lesson_plan_id', $lessonPlan->id)
                ->where('user_id', Auth::id())
                ->exists()) {
            return $request->expectsJson()
                ? response()->json(['message' => 'View the plan before voting.'], 403)
                : back()->with('error', 'Please view the plan before voting.');
        }

        // Check if the user has already voted on this version
        $existingVote = Vote::where('lesson_plan_id', $lessonPlan->id)
            ->where('user_id', Auth::id())
            ->first();

        if ($existingVote) {
            if ($existingVote->value === (int) $data['value']) {
                // Same vote direction again — toggle it off (remove the vote)
                $existingVote->delete();
            } else {
                // Switching direction (upvote ↔ downvote)
                $existingVote->update(['value' => $data['value']]);
            }
        } else {
            // No existing vote — create a new one.
            // Wrapped in try/catch to handle the rare race condition where two
            // concurrent requests both pass the "no existing vote" check above
            // and then both attempt to insert, triggering a unique index violation.
            try {
                Vote::create([
                    'lesson_plan_id' => $lessonPlan->id,
                    'user_id'        => Auth::id(),
                    'value'          => $data['value'],
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                // SQLSTATE 23000 = integrity constraint violation (duplicate key).
                // The other request already inserted — treat as a no-op.
                if ($e->getCode() !== '23000') {
                    throw $e;
                }
            }
        }

        // Recalculate the cached vote_score column on the lesson plan
        // (avoids expensive SUM() queries on every page load)
        $lessonPlan->recalculateVoteScore();
        $lessonPlan->refresh();

        // For AJAX requests (inline dashboard vote buttons), return JSON.
        // For standard form submissions (show page), redirect back as usual.
        if ($request->expectsJson()) {
            $newVote = Vote::where('lesson_plan_id', $lessonPlan->id)
                ->where('user_id', Auth::id())
                ->first();
            return response()->json([
                'score'    => $lessonPlan->vote_score,
                'userVote' => $newVote ? $newVote->value : null,
            ]);
        }

        return back()->with('success', 'Vote recorded.');
    }
}
