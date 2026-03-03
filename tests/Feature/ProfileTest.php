<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * This app does not have a standalone /profile page — account management
 * is handled via modals on the dashboard. These tests cover basic auth
 * behavior that the default Breeze ProfileTest would cover.
 */
class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_verified_user_can_access_dashboard(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
    }

    public function test_unauthenticated_user_sees_public_dashboard(): void
    {
        // Dashboard is publicly visible — no auth required.
        $response = $this->get(route('dashboard'));

        $response->assertStatus(200);
    }

    public function test_password_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put(route('password.update'), [
            'current_password'      => 'password',
            'password'              => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertSessionHasNoErrors();
    }
}
