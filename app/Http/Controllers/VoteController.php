<?php

namespace App\Http\Controllers;

use App\Models\LessonPlan;
use App\Models\Vote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VoteController extends Controller
{
    /**
     * Cast an upvote or downvote on a lesson plan version.
     * Each user gets one vote per version. Voting the same way again
     * removes the vote (toggle behavior). Voting the opposite way switches it.
     */
    public function store(Request $request, LessonPlan $lessonPlan)
    {
        $data = $request->validate([
            'value' => 'required|integer|in:-1,1',
        ]);

        // Prevent authors from voting on their own plans
        if ($lessonPlan->author_id === Auth::id()) {
            return back()->with('error', 'You cannot vote on your own lesson plan.');
        }

        $existingVote = Vote::where('lesson_plan_id', $lessonPlan->id)
            ->where('user_id', Auth::id())
            ->first();

        if ($existingVote) {
            if ($existingVote->value === (int) $data['value']) {
                // Same vote again — toggle it off (remove vote)
                $existingVote->delete();
            } else {
                // Switching from upvote to downvote or vice versa
                $existingVote->update(['value' => $data['value']]);
            }
        } else {
            // New vote
            Vote::create([
                'lesson_plan_id' => $lessonPlan->id,
                'user_id'        => Auth::id(),
                'value'          => $data['value'],
            ]);
        }

        // Recalculate cached score
        $lessonPlan->recalculateVoteScore();

        return back()->with('success', 'Vote recorded.');
    }
}
