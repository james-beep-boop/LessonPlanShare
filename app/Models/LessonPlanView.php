<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Tracks whether a user has visited the detail page of a lesson plan.
 * Used to gate voting: users must view a plan before they can vote on it.
 *
 * Created via LessonPlanView::firstOrCreate() in LessonPlanController::show().
 */
class LessonPlanView extends Model
{
    // Only created_at; no updated_at (we only need to know IF a view happened)
    public $timestamps = false;

    const CREATED_AT = 'created_at';

    protected $fillable = ['user_id', 'lesson_plan_id'];
}
