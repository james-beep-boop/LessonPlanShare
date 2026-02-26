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
 * - The 'name' column stores the Teacher Name chosen at registration.
 *   Teacher Names must be unique (enforced in AuthenticatedSessionController).
 * - is_admin grants access to the /admin panel. Set via tinker after deploy:
 *   User::where('email','priority2@protonmail.ch')->update(['is_admin'=>true]);
 * - Routes that require verified email use the ['auth', 'verified']
 *   middleware (see routes/web.php).
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',      // Teacher Name (unique, chosen at registration)
        'email',     // Login identifier and email for notifications
        'password',  // Bcrypt-hashed password
        'is_admin',  // Administrator flag — grants access to /admin
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_admin'          => 'boolean',
            'password'          => 'hashed',
        ];
    }

    /** True if this user has administrator privileges. */
    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    /** All lesson plans authored by this user. */
    public function lessonPlans()
    {
        return $this->hasMany(LessonPlan::class, 'author_id');
    }

    /** All votes this user has cast across all lesson plan versions. */
    public function votes()
    {
        return $this->hasMany(Vote::class);
    }
}
