<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vote extends Model
{
    use HasFactory;

    protected $fillable = [
        'lesson_plan_id',
        'user_id',
        'value',
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
     * Is this an upvote?
     */
    public function getIsUpvoteAttribute(): bool
    {
        return $this->value === 1;
    }

    /**
     * Is this a downvote?
     */
    public function getIsDownvoteAttribute(): bool
    {
        return $this->value === -1;
    }
}
