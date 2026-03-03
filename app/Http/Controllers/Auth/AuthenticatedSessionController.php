<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Maximum failed login attempts before rate limiting kicks in.
     * 5 failures per 60 seconds per (email + IP) throttle key.
     */
    private const MAX_ATTEMPTS  = 5;
    private const DECAY_SECONDS = 60;

    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming sign-in request.
     *
     * Three cases (registration is handled by RegisteredUserController via POST /register):
     *   1. Email not in DB → return error in 'login' bag directing user to Sign Up.
     *   2. Email exists, NOT yet verified → verify password, then resend verification.
     *   3. Email exists, verified → authenticate. Wrong password = error in 'login' bag.
     *
     * Rate limiting: 5 failed attempts per 60 s per (lower-cased email + IP) key.
     * All errors go to the 'login' named bag so the Sign In modal re-opens on redirect.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $email       = $request->input('email');
        $throttleKey = $this->throttleKey($request);

        // Guard: reject immediately if the rate limit has already been exceeded.
        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()
                ->withErrors([
                    'email' => "Too many login attempts. Please try again in {$seconds} seconds.",
                ], 'login')
                ->withInput(['email' => $email]);
        }

        $user = User::where('email', $email)->first();

        // Case 1: email not found — direct user to Sign Up instead.
        if (! $user) {
            RateLimiter::hit($throttleKey, self::DECAY_SECONDS);
            return back()
                ->withErrors(['email' => 'No account found with that email. Please use Sign Up to create one.'], 'login')
                ->withInput(['email' => $email]);
        }

        // Case 2: email exists but not yet verified — verify password first, then resend.
        // Password check prevents anyone who knows an email address from spamming resend.
        if (! $user->hasVerifiedEmail()) {
            if (! Hash::check($request->input('password'), $user->password)) {
                RateLimiter::hit($throttleKey, self::DECAY_SECONDS);
                return back()
                    ->withErrors(['email' => 'These credentials do not match our records.'], 'login')
                    ->withInput(['email' => $email]);
            }

            $user->sendEmailVerificationNotification();

            Auth::login($user);
            $request->session()->regenerate();
            RateLimiter::clear($throttleKey);

            return redirect(route('verification.notice', absolute: false));
        }

        // Case 3: verified account — authenticate. Wrong password returns error in 'login' bag.
        if (! Hash::check($request->input('password'), $user->password)) {
            RateLimiter::hit($throttleKey, self::DECAY_SECONDS);
            return back()
                ->withErrors(['email' => 'These credentials do not match our records.'], 'login')
                ->withInput(['email' => $email]);
        }

        Auth::login($user);
        $request->session()->regenerate();
        RateLimiter::clear($throttleKey);

        return redirect(route('dashboard', absolute: false));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * Build the rate-limiter key: lower-cased email + pipe + IP address.
     * Using both prevents an attacker from rotating IPs to bypass per-IP limits,
     * and prevents enumeration of a single email from a distributed botnet.
     */
    private function throttleKey(Request $request): string
    {
        return Str::lower($request->input('email', '')) . '|' . $request->ip();
    }
}
