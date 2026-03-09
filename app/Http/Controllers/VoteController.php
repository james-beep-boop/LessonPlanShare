<?php

namespace App\Http\Controllers;

use App\Models\LessonPlan;
use App\Models\LessonPlanEngagement;
use App\Models\Vote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Handles upvote/downvote/reset actions on lesson plan versions.
 *
 * Voting behavior:
 * - Each user gets exactly one active vote per lesson plan version.
 * - Voting the same direction again REMOVES the vote (toggle off).
 * - Voting the opposite direction SWITCHES the vote.
 * - Users cannot vote on their own plans (self-vote prevention).
 * - The cached vote_score on the lesson plan is recalculated after each action.
 *
 * Engagement gate:
 * - Users must have engaged with the plan before voting (downloaded it,
 *   or viewed it in Google Docs / MS Office).
 * - Enforced server-side to prevent API-level bypasses.
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

        // Guard: users cannot vote on their own plans.
        if ($lessonPlan->author_id === Auth::id()) {
            return $request->expectsJson()
                ? response()->json(['message' => 'You cannot vote on your own plan.'], 403)
                : back()->with('error', 'You cannot vote on your own plan.');
        }

        // Guard: must have engaged with the plan (download or external viewer) before voting.
        $hasEngaged = LessonPlanEngagement::where('lesson_plan_id', $lessonPlan->id)
            ->where('user_id', Auth::id())
            ->exists();

        if (! $hasEngaged) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Download or view the plan in an external viewer before voting.'], 403)
                : back()->with('error', 'Please download or open the plan in an external viewer before voting.');
        }

        // Check if the user has already voted on this version
        $existingVote = Vote::where('lesson_plan_id', $lessonPlan->id)
            ->where('user_id', Auth::id())
            ->first();

        $newValue = (int) $data['value'];
        $delta    = 0;

        if ($existingVote) {
            if ($existingVote->value === $newValue) {
                // Same direction — toggle off (remove the vote)
                $delta = -$existingVote->value;
                $existingVote->delete();
            } else {
                // Switching direction (upvote ↔ downvote): delta spans both sides
                $delta = $newValue - $existingVote->value;  // e.g. +1 - (-1) = +2
                $existingVote->update(['value' => $newValue]);
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
                    'value'          => $newValue,
                ]);
                $delta = $newValue;
            } catch (\Illuminate\Database\QueryException $e) {
                // SQLSTATE 23000 = integrity constraint violation (duplicate key).
                // The other request already inserted — treat as a no-op (delta stays 0).
                if ($e->getCode() !== '23000') {
                    throw $e;
                }
            }
        }

        // Atomically adjust the cached vote_score by the exact delta.
        // Using increment/decrement avoids the read-then-write race condition.
        $lessonPlan->adjustVoteScore($delta);
        $lessonPlan->refresh();

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

    /**
     * Remove (reset) the authenticated user's vote on a lesson plan version.
     *
     * Used by the "Reset Vote" button on the plan detail page.
     * Always returns JSON (only called via AJAX).
     */
    public function destroy(Request $request, LessonPlan $lessonPlan)
    {
        $vote = Vote::where('lesson_plan_id', $lessonPlan->id)
            ->where('user_id', Auth::id())
            ->first();

        if ($vote) {
            $delta = -$vote->value;  // removing a +1 vote → -1 delta; removing a -1 → +1 delta
            $vote->delete();
            $lessonPlan->adjustVoteScore($delta);
            $lessonPlan->refresh();
        }

        if ($request->expectsJson()) {
            return response()->json([
                'score'    => $lessonPlan->vote_score,
                'userVote' => null,
            ]);
        }

        return back()->with('success', 'Vote removed.');
    }
}
