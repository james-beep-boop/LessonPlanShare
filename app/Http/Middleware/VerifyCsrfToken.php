<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * URIs that should be excluded from CSRF verification.
     *
     * Logout is excluded so that a second browser tab can submit the logout
     * form after the session (and its CSRF token) was already destroyed by
     * the first tab's logout — otherwise the second tab gets a 419.
     */
    protected $except = [
        'logout',
    ];
}
