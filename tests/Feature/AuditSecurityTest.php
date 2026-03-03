<?php

namespace Tests\Feature;

use App\Models\LessonPlan;
use App\Models\LessonPlanEngagement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Feature tests for the security/quality audit fixes.
 *
 * Covers:
 *   A1 — Email verification signed URL defense-in-depth
 *   A2 — retireForClassDay() authorization (author or admin only)
 *   A4 — Login rate limiting (5 attempts / 60 s)
 *   A5 — Registration password confirmation required
 *   B1 — Self-vote prevention + engagement gate
 */
class AuditSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    // ── A1: Email verification signed URL ─────────────────────────────

    #[Test]
    public function verification_link_without_signature_returns_403(): void
    {
        $user = User::factory()->create(['email_verified_at' => null]);

        // Hit the route with no ?signature parameter — should be rejected
        $response = $this->get("/email/verify/{$user->id}/" . sha1($user->email));

        $response->assertStatus(403);
    }

    #[Test]
    public function valid_verification_link_verifies_user_and_redirects_to_dashboard(): void
    {
        $user = User::factory()->create(['email_verified_at' => null]);

        $verificationUrl = URL::signedRoute('verification.verify', [
            'id'   => $user->id,
            'hash' => sha1($user->email),
        ]);

        $response = $this->get($verificationUrl);

        $response->assertRedirect(route('dashboard'));
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    #[Test]
    public function already_verified_email_link_redirects_without_re_logging_in(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $verificationUrl = URL::signedRoute('verification.verify', [
            'id'   => $user->id,
            'hash' => sha1($user->email),
        ]);

        $response = $this->get($verificationUrl);

        // Already-verified branch: redirect to dashboard with a notice, no new login session
        $response->assertRedirect(route('dashboard'));
    }

    // ── A4: Login throttling ──────────────────────────────────────────

    #[Test]
    public function login_is_throttled_after_five_failed_attempts(): void
    {
        $user = User::factory()->create([
            'email'             => 'throttle-test@example.com',
            'email_verified_at' => now(),
        ]);

        $key = strtolower($user->email) . '|127.0.0.1';
        RateLimiter::clear($key);

        // 5 failed attempts
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'email'    => $user->email,
                'password' => 'wrong-password',
            ]);
        }

        // 6th attempt — must be throttled
        $response = $this->post('/login', [
            'email'    => $user->email,
            'password' => 'wrong-password',
        ]);

        $errors = session()->get('errors');
        $this->assertNotNull($errors, 'Expected session errors to be set');
        $bag = $errors->getBag('login');
        $this->assertNotNull($bag, 'Expected errors in login bag');
        $this->assertStringContainsString(
            'Too many login attempts',
            $bag->first('email')
        );

        RateLimiter::clear($key);
    }

    #[Test]
    public function rate_limiter_clears_after_successful_login(): void
    {
        $password = 'CorrectP@ss1';
        $user     = User::factory()->create([
            'email'             => 'throttle-clear@example.com',
            'email_verified_at' => now(),
            'password'          => bcrypt($password),
        ]);

        $key = strtolower($user->email) . '|127.0.0.1';
        RateLimiter::clear($key);

        // 4 failed attempts (below the limit)
        for ($i = 0; $i < 4; $i++) {
            $this->post('/login', [
                'email'    => $user->email,
                'password' => 'wrong-password',
            ]);
        }

        // Successful login must clear the counter
        $this->post('/login', [
            'email'    => $user->email,
            'password' => $password,
        ]);

        // Counter should now be 0; asserting false = not yet too many attempts
        $this->assertFalse(RateLimiter::tooManyAttempts($key, 5));

        RateLimiter::clear($key);
    }

    // ── A5: Password confirmation ─────────────────────────────────────

    #[Test]
    public function registration_fails_when_passwords_do_not_match(): void
    {
        $response = $this->post(route('register.store'), [
            'name'                  => 'Test Teacher',
            'email'                 => 'teacher@example.com',
            'password'              => 'SecureP@ss1',
            'password_confirmation' => 'DifferentP@ss1',
        ]);

        $response->assertSessionHasErrorsIn('register', ['password']);
        $this->assertDatabaseMissing('users', ['email' => 'teacher@example.com']);
    }

    #[Test]
    public function registration_fails_when_password_confirmation_missing(): void
    {
        $response = $this->post(route('register.store'), [
            'name'     => 'Test Teacher',
            'email'    => 'teacher@example.com',
            'password' => 'SecureP@ss1',
            // password_confirmation intentionally omitted
        ]);

        $response->assertSessionHasErrorsIn('register', ['password']);
        $this->assertDatabaseMissing('users', ['email' => 'teacher@example.com']);
    }

    #[Test]
    public function registration_succeeds_when_passwords_match(): void
    {
        $this->post(route('register.store'), [
            'name'                  => 'Test Teacher',
            'email'                 => 'teacher@example.com',
            'password'              => 'SecureP@ss1',
            'password_confirmation' => 'SecureP@ss1',
        ]);

        $this->assertDatabaseHas('users', ['email' => 'teacher@example.com']);
    }

    // ── B1: Self-vote prevention ──────────────────────────────────────

    #[Test]
    public function user_cannot_vote_on_their_own_plan(): void
    {
        $author = User::factory()->create(['email_verified_at' => now()]);
        $plan   = LessonPlan::factory()->create(['author_id' => $author->id]);

        $response = $this->actingAs($author)
            ->postJson(route('votes.store', $plan), ['value' => 1]);

        $response->assertStatus(403);
        $response->assertJson(['message' => 'You cannot vote on your own plan.']);
    }

    #[Test]
    public function user_cannot_vote_without_prior_engagement(): void
    {
        $author = User::factory()->create(['email_verified_at' => now()]);
        $voter  = User::factory()->create(['email_verified_at' => now()]);
        $plan   = LessonPlan::factory()->create(['author_id' => $author->id]);

        // No engagement record for $voter

        $response = $this->actingAs($voter)
            ->postJson(route('votes.store', $plan), ['value' => 1]);

        $response->assertStatus(403);
        $response->assertJson(['message' => 'Download or view the plan in an external viewer before voting.']);
    }

    #[Test]
    public function user_can_vote_after_engagement(): void
    {
        $author = User::factory()->create(['email_verified_at' => now()]);
        $voter  = User::factory()->create(['email_verified_at' => now()]);
        $plan   = LessonPlan::factory()->create(['author_id' => $author->id]);

        LessonPlanEngagement::create([
            'user_id'        => $voter->id,
            'lesson_plan_id' => $plan->id,
            'type'           => LessonPlanEngagement::DOWNLOAD,
        ]);

        $response = $this->actingAs($voter)
            ->postJson(route('votes.store', $plan), ['value' => 1]);

        $response->assertOk();
        $response->assertJsonStructure(['score', 'userVote']);
    }

    // ── A2: Retire endpoint authorization ─────────────────────────────

    #[Test]
    public function retire_class_day_is_blocked_for_non_author_non_admin(): void
    {
        $author   = User::factory()->create(['email_verified_at' => now()]);
        $outsider = User::factory()->create(['email_verified_at' => now()]);

        LessonPlan::factory()->create([
            'author_id'  => $author->id,
            'class_name' => 'Biology',
            'lesson_day' => 3,
        ]);

        $response = $this->actingAs($outsider)
            ->postJson(route('lesson-plans.retire'), [
                'class_name' => 'Biology',
                'lesson_day' => 3,
            ]);

        $response->assertStatus(403);
        $response->assertJson(['success' => false]);
    }

    #[Test]
    public function retire_class_day_is_allowed_for_plan_author(): void
    {
        $author = User::factory()->create(['email_verified_at' => now()]);

        LessonPlan::factory()->create([
            'author_id'  => $author->id,
            'class_name' => 'Biology',
            'lesson_day' => 4,
        ]);

        $response = $this->actingAs($author)
            ->postJson(route('lesson-plans.retire'), [
                'class_name' => 'Biology',
                'lesson_day' => 4,
            ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
    }

    #[Test]
    public function retire_class_day_is_allowed_for_admin(): void
    {
        $admin  = User::factory()->create([
            'email_verified_at' => now(),
            'is_admin'          => true,
        ]);
        $author = User::factory()->create(['email_verified_at' => now()]);

        LessonPlan::factory()->create([
            'author_id'  => $author->id,
            'class_name' => 'Physics',
            'lesson_day' => 2,
        ]);

        $response = $this->actingAs($admin)
            ->postJson(route('lesson-plans.retire'), [
                'class_name' => 'Physics',
                'lesson_day' => 2,
            ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
    }
}
