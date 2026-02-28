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
 * Handles new user registration via the Sign Up modal (POST /register).
 *
 * Requires Teacher Name + Teacher Email + Password.
 * Teacher Name uniqueness is enforced; email uniqueness is enforced.
 * All validation errors are sent to the 'register' named error bag so the
 * Sign Up modal re-opens automatically on redirect back.
 *
 * GET /register is separately redirected to the dashboard (see routes/auth.php)
 * so there is no standalone registration page — only the modal.
 */
class RegisteredUserController extends Controller
{
    /**
     * Display the registration view — not used; GET /register redirects to dashboard.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle the Sign Up form submission.
     *
     * Validates Teacher Name + email + password, enforces uniqueness on both,
     * creates the user, sends the verification email, and logs them in.
     * Errors are returned in the 'register' bag so the Sign Up modal re-opens.
     */
    public function store(Request $request): RedirectResponse
    {
        $name  = trim($request->input('name', ''));
        $email = strtolower(trim($request->input('email', '')));

        // Validate field formats first — errors go to the 'register' named bag
        // so the Sign Up modal re-opens automatically on redirect.
        $request->validateWithBag('register', [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', Rules\Password::defaults()],
        ]);

        // Check email uniqueness (return error in 'register' bag)
        if (User::where('email', $email)->exists()) {
            return back()
                ->withErrors(['email' => 'An account with that email already exists. Please sign in instead.'], 'register')
                ->withInput(['name' => $name, 'email' => $email]);
        }

        // Check Teacher Name uniqueness (return error in 'register' bag)
        if (User::where('name', $name)->exists()) {
            return back()
                ->withErrors(['name' => 'That Teacher Name is already taken. Please choose another.'], 'register')
                ->withInput(['name' => $name, 'email' => $email]);
        }

        $user = User::create([
            'name'     => $name,
            'email'    => $email,
            'password' => Hash::make($request->input('password')),
        ]);

        event(new Registered($user));   // triggers the verification email

        Auth::login($user);
        $request->session()->regenerate();

        return redirect(route('verification.notice', absolute: false));
    }
}
