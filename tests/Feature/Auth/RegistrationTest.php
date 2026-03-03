<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Registration is modal-based in this app — there is no standalone register page.
 * GET /register redirects to the dashboard (where the Sign Up modal lives).
 * POST /register.store creates the account and sends to email verification.
 */
class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_redirects_to_dashboard(): void
    {
        // No standalone register page — redirect to dashboard where the modal is.
        $response = $this->get('/register');

        $response->assertRedirect(route('dashboard'));
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post(route('register.store'), [
            'name'                  => 'Test Teacher',
            'email'                 => 'teacher@example.com',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $this->assertDatabaseHas('users', ['email' => 'teacher@example.com']);
        // After registration, users are redirected to the email verification notice.
        $response->assertRedirect(route('verification.notice'));
    }
}
