<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Exclude logout from CSRF validation so a second browser tab can
        // submit the logout form even after the first tab already destroyed
        // the session (and its CSRF token), preventing a 419 double-logout.
        $middleware->validateCsrfTokens(['logout']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
