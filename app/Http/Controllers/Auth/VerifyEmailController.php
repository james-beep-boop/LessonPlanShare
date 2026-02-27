<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Custom email verification controller that does NOT require the user
 * to already be logged in.
 *
 * Why: When a user clicks the verification link in their email, it often
 * opens in a new browser tab (or even a different browser/device). The
 * default Breeze controller wraps this route in 'auth' middleware, which
 * means the user must already have a session — otherwise they get a 403.
 *
 * This controller validates the signed URL (cryptographically secure),
 * looks up the user by ID, verifies the hash matches, marks the email
 * as verified, logs the user in, and redirects to the dashboard.
 */
class VerifyEmailController extends Controller
{
    public function __invoke(Request $request, int $id, string $hash): RedirectResponse
    {
        $user = User::findOrFail($id);

        // Verify the hash matches the user's email (same check Breeze does)
        if (! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            abort(403, 'Invalid verification link.');
        }

        // Already verified? Redirect without logging in.
        // Do NOT call Auth::login() here — this branch can be triggered by
        // replaying the verification link (e.g. clicking it a second time, or
        // sharing the link). Granting a session via a replayed signed URL is a
        // security risk; the user should sign in normally instead.
        if ($user->hasVerifiedEmail()) {
            return redirect()->route('dashboard')->with('success', 'Your email is already verified. Please sign in.');
        }

        // Mark as verified and fire the Verified event
        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        // Log the user in so they land on the dashboard ready to go
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('success', 'Email verified! Welcome to ARES Education.');
    }
}
