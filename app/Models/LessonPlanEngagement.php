<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Tracks meaningful engagement by an authenticated user with a lesson plan version.
 *
 * Engagement types (see class constants):
 *   - GOOGLE_DOCS: user opened the plan in the Google Docs viewer
 *   - MS_OFFICE:   user opened the plan in the Microsoft Office viewer
 *   - DOWNLOAD:    user downloaded the plan file
 *
 * Created via LessonPlanEngagement::firstOrCreate() in:
 *   - LessonPlanController::trackEngagement() — for viewer clicks (AJAX)
 *   - LessonPlanController::download()        — for file downloads
 *
 * Used by VoteController::store() to gate voting: users must have engaged with
 * (or authored) a plan before they can vote on it.
 */
class LessonPlanEngagement extends Model
{
    // Only created_at; no updated_at (we only need to know IF engagement happened)
    public $timestamps = false;

    const CREATED_AT = 'created_at';

    const GOOGLE_DOCS = 'google_docs';
    const MS_OFFICE   = 'ms_office';
    const DOWNLOAD    = 'download';

    protected $fillable = ['user_id', 'lesson_plan_id', 'type'];
}
