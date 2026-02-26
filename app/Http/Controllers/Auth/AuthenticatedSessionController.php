<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     *
     * Three cases:
     *   1. Email not in DB → auto-register, send verification email, go to "Check Your Email".
     *   2. Email exists, NOT yet verified → update password (in case they forgot it),
     *      resend verification email, go to "Check Your Email".
     *   3. Email exists, verified → normal authentication. Wrong password returns an error.
     *
     * This means users never need to find a separate "Sign Up" form. Entering any
     * email/password combination will either log them in or start the verification flow.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $email    = $request->input('email');
        $password = $request->input('password');

        $user = User::where('email', $email)->first();

        // Case 1: brand-new email — create account and send verification email.
        if (! $user) {
            $user = User::create([
                'name'     => $email,
                'email'    => $email,
                'password' => Hash::make($password),
            ]);

            event(new Registered($user));   // sends the verification email

            Auth::login($user);

            return redirect(route('verification.notice', absolute: false));
        }

        // Case 2: account exists but email not yet verified — update password and resend.
        // (Covers the case where the user previously registered but never confirmed,
        //  and may have forgotten the original password.)
        if (! $user->hasVerifiedEmail()) {
            $user->update(['password' => Hash::make($password)]);

            $user->sendEmailVerificationNotification();

            Auth::login($user);

            return redirect(route('verification.notice', absolute: false));
        }

        // Case 3: verified account — standard authentication. Wrong password = error.
        $request->authenticate();

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
