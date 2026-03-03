<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Email verification uses a custom VerifyEmailController that validates a
 * signed URL, marks the email as verified, auto-logs in the user, and
 * redirects to the dashboard — no ?verified=1 param, no separate event.
 */
class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_verification_screen_can_be_rendered(): void
    {
        $user = User::factory()->create(['email_verified_at' => null]);

        $response = $this->actingAs($user)->get(route('verification.notice'));

        $response->assertStatus(200);
    }

    public function test_email_can_be_verified(): void
    {
        $user = User::factory()->create(['email_verified_at' => null]);

        $verificationUrl = URL::signedRoute('verification.verify', [
            'id'   => $user->id,
            'hash' => sha1($user->email),
        ]);

        $response = $this->get($verificationUrl);

        // Our VerifyEmailController redirects to dashboard (no ?verified=1 suffix).
        $response->assertRedirect(route('dashboard'));
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_email_is_not_verified_with_invalid_hash(): void
    {
        $user = User::factory()->create(['email_verified_at' => null]);

        // Sign the URL with the correct ID but wrong email hash — should be rejected.
        $verificationUrl = URL::signedRoute('verification.verify', [
            'id'   => $user->id,
            'hash' => sha1('wrong-email@example.com'),
        ]);

        $this->get($verificationUrl);

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }
}
