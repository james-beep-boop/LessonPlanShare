<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Records every raw download event for a lesson plan.
 *
 * Unlike lesson_plan_engagements (unique per user/plan/type),
 * this table has no unique constraint — each download click is its own row,
 * enabling true cumulative download counts over time.
 */
class LessonPlanDownload extends Model
{
    public $timestamps = false;

    const CREATED_AT = 'created_at';

    protected $fillable = ['lesson_plan_id', 'user_id'];
}
