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
 * - All authenticated users can vote, including the plan's own author.
 * - The cached vote_score on the lesson plan is recalculated after each action.
 *
 * Engagement gate:
 * - Users must have engaged with the plan before voting (downloaded it,
 *   or viewed it in Google Docs / MS Office). Authors are exempt.
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

        // Guard: must have engaged with the plan before voting.
        // Authors are exempt; everyone else needs a download or viewer engagement.
        $canVote = $lessonPlan->author_id === Auth::id()
            || LessonPlanEngagement::where('lesson_plan_id', $lessonPlan->id)
                ->where('user_id', Auth::id())
                ->exists();

        if (! $canVote) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Download or view the plan in an external viewer before voting.'], 403)
                : back()->with('error', 'Please download or open the plan in an external viewer before voting.');
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
            $vote->delete();
            $lessonPlan->recalculateVoteScore();
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
