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
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     *
     * Three cases:
     *   1. Email not in DB → validate Teacher Name uniqueness, create account,
     *      send verification email, redirect to "Check Your Email".
     *      User is NOT logged in until they click the verification link.
     *   2. Email exists, NOT yet verified → resend verification email.
     *   3. Email exists, verified → standard authentication (wrong password = error).
     *
     * Teacher Name is only used (and validated) in Case 1.
     * For Cases 2 and 3, the name field is present in the form but ignored.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $email = $request->input('email');
        $user  = User::where('email', $email)->first();

        // Case 1: brand-new email — register with Teacher Name.
        if (! $user) {
            $name = trim($request->input('name', ''));

            if ($name === '') {
                return back()
                    ->withErrors(['name' => 'Teacher Name is required for new accounts.'])
                    ->withInput();
            }

            if (User::where('name', $name)->exists()) {
                return back()
                    ->withErrors(['name' => 'That Teacher Name is already taken. Please choose another.'])
                    ->withInput();
            }

            $user = User::create([
                'name'     => $name,
                'email'    => $email,
                'password' => Hash::make($request->input('password')),
            ]);

            event(new Registered($user));   // sends the verification email

            // Log in so the verify-email page can show their email address and
            // offer the Resend button. Unverified users appear as guests in the UI
            // (no username shown, no nav links) because layout.blade.php checks
            // hasVerifiedEmail() on every protected element.
            Auth::login($user);
            $request->session()->regenerate();

            return redirect(route('verification.notice', absolute: false));
        }

        // Case 2: email exists but not yet verified — resend verification email.
        if (! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();

            Auth::login($user);
            $request->session()->regenerate();

            return redirect(route('verification.notice', absolute: false));
        }

        // Case 3: verified account — standard authentication. Wrong password = error.
        $request->authenticate();
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
