<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Represents a single upvote (+1) or downvote (-1) on a lesson plan version.
 *
 * Constraints:
 * - One vote per user per lesson plan version (enforced by unique index
 *   on [lesson_plan_id, user_id] in the migration).
 * - Value must be +1 or -1 (enforced by VoteController validation).
 * - Authors cannot vote on their own plans (enforced by VoteController).
 *
 * The sum of all votes for a plan is cached in lesson_plans.vote_score
 * to avoid expensive aggregation on every page load.
 */
class Vote extends Model
{
    use HasFactory;

    /**
     * Mass-assignable attributes.
     */
    protected $fillable = [
        'lesson_plan_id',  // FK to the lesson_plans table
        'user_id',         // FK to the users table (who cast this vote)
        'value',           // +1 (upvote) or -1 (downvote)
    ];

    /**
     * The lesson plan version this vote belongs to.
     */
    public function lessonPlan()
    {
        return $this->belongsTo(LessonPlan::class);
    }

    /**
     * The user who cast this vote.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Is this an upvote (+1)?
     */
    public function getIsUpvoteAttribute(): bool
    {
        return $this->value === 1;
    }

    /**
     * Is this a downvote (-1)?
     */
    public function getIsDownvoteAttribute(): bool
    {
        return $this->value === -1;
    }
}
