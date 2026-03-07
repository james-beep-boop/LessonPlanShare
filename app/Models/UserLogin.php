<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Records every successful login event for a user.
 *
 * Unlike lesson_plan_engagements (which is unique per user/plan/type),
 * this table has no unique constraint — each login is its own row,
 * allowing cumulative login counts to be tracked over time.
 */
class UserLogin extends Model
{
    public $timestamps = false;

    const CREATED_AT = 'created_at';

    protected $fillable = ['user_id'];
}
