<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Pivot model for user favorites.
 * A user can favorite any lesson plan version; toggled via FavoriteController.
 */
class Favorite extends Model
{
    // Only created_at; no updated_at (we only care if it exists, not when last changed)
    public $timestamps = false;
    const CREATED_AT = 'created_at';

    protected $fillable = ['user_id', 'lesson_plan_id'];
}
