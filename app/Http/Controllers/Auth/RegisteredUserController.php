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
 * Fallback registration controller — NOT used in normal operation.
 *
 * All registration is handled by AuthenticatedSessionController::store()
 * via the merged auth modal (three-case logic: new email / unverified / verified).
 * That flow requires Teacher Name + email + password and enforces name uniqueness.
 *
 * The routes/auth.php file redirects both GET /register and POST /register
 * to the dashboard, so neither create() nor store() below is ever reached
 * in the normal user flow. They are retained only as a documented dead-code
 * stub so Breeze's route names remain resolvable.
 *
 * If a standalone register page is ever reintroduced, store() must be updated
 * to accept and validate the 'teacher_name' field (matching the modal flow in
 * AuthenticatedSessionController).
 */
class RegisteredUserController extends Controller
{
    /**
     * Display the registration view (not reached — GET /register redirects to dashboard).
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle a registration request (not reached — POST /register redirects to dashboard).
     *
     * NOTE: This store() uses the old "email as name" pattern and does NOT
     * collect Teacher Name. It must NOT be made reachable without first being
     * updated to match the AuthenticatedSessionController three-case flow.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email'    => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name'     => $request->email,   // stub only — real flow uses teacher_name
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('verification.notice', absolute: false));
    }
}
