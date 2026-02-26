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
     * If the email is not in the database, auto-register the user with the
     * supplied password, send a verification email, and redirect to the
     * "Check Your Email" page — no separate Sign Up step required.
     *
     * If the email exists, authenticate normally. Wrong passwords still produce
     * the standard Breeze error.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $email = $request->input('email');

        // Auto-register: if no account exists for this email, create one now.
        if (! User::where('email', $email)->exists()) {
            $user = User::create([
                'name'     => $email,   // username = email address (same as RegisteredUserController)
                'email'    => $email,
                'password' => Hash::make($request->input('password')),
            ]);

            event(new Registered($user));   // sends the verification email

            Auth::login($user);

            return redirect(route('verification.notice', absolute: false));
        }

        // Existing account — standard authentication (wrong password returns error).
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
