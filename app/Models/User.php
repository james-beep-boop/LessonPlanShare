<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * User model for the ARES Education Lesson Plan Archive.
 *
 * Key design decisions:
 * - Implements MustVerifyEmail: new users must click a confirmation link
 *   in their email before they can access authenticated routes.
 * - The 'name' column stores the user's email address (same as 'email').
 *   This simplifies the registration form to a single "Username" field
 *   while maintaining compatibility with Laravel's default auth system
 *   which expects a 'name' column.
 * - Routes that require verified email use the ['auth', 'verified']
 *   middleware (see routes/web.php).
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    /**
     * Mass-assignable attributes.
     */
    protected $fillable = [
        'name',      // Display name (set to email address at registration)
        'email',     // Login identifier and email for notifications
        'password',  // Bcrypt-hashed password
    ];

    /**
     * Attributes hidden from array/JSON serialization (security).
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Attribute type casts.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    /**
     * All lesson plans authored by this user.
     */
    public function lessonPlans()
    {
        return $this->hasMany(LessonPlan::class, 'author_id');
    }

    /**
     * All votes this user has cast across all lesson plan versions.
     */
    public function votes()
    {
        return $this->hasMany(Vote::class);
    }
}
