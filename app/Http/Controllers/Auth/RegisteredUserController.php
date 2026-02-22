<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

/**
 * Overrides the default Breeze RegisteredUserController.
 *
 * Simplification: the "email" field serves as both the login identifier
 * AND the display name. There is no separate "name" field in our sign-up
 * form — we store the email address in both the name and email columns.
 *
 * The User model implements MustVerifyEmail, so after registration a
 * verification email is sent automatically via the Registered event.
 * The user must click the confirmation link before they can access
 * authenticated routes that use the 'verified' middleware.
 */
class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     * (Our modal in layout.blade.php handles the UI, but Breeze may
     *  still route here for standalone page fallback.)
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email'    => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name'     => $request->email,   // username = email address
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('verification.notice', absolute: false));
    }
}
