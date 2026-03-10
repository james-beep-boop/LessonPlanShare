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

        // Hit the route with no ?signature parameter — should be rejected.
        // Use route() so the correct /verify-email/{id}/{hash} URL is built,
        // but without URL::signedRoute() so there is no signature parameter.
        $url = route('verification.verify', [
            'id'   => $user->id,
            'hash' => sha1($user->email),
        ]);
        $response = $this->get($url);

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
            'grade'      => 10,
            'lesson_day' => 3,
        ]);

        $response = $this->actingAs($outsider)
            ->postJson(route('lesson-plans.retire'), [
                'class_name' => 'Biology',
                'grade'      => 10,
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
            'grade'      => 10,
            'lesson_day' => 4,
        ]);

        $response = $this->actingAs($author)
            ->postJson(route('lesson-plans.retire'), [
                'class_name' => 'Biology',
                'grade'      => 10,
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
            'grade'      => 10,
            'lesson_day' => 2,
        ]);

        $response = $this->actingAs($admin)
            ->postJson(route('lesson-plans.retire'), [
                'class_name' => 'Physics',
                'grade'      => 10,
                'lesson_day' => 2,
            ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
    }

    #[Test]
    public function retire_class_day_non_admin_only_archives_own_plans(): void
    {
        $author   = User::factory()->create(['email_verified_at' => now()]);
        $other    = User::factory()->create(['email_verified_at' => now()]);

        // $author owns one plan; $other owns a second plan in the same class/grade/day
        $ownPlan   = LessonPlan::factory()->create([
            'author_id'  => $author->id,
            'class_name' => 'Geography',
            'grade'      => 10,
            'lesson_day' => 1,
        ]);
        $otherPlan = LessonPlan::factory()->create([
            'author_id'     => $other->id,
            'class_name'    => 'Geography',
            'grade'         => 10,
            'lesson_day'    => 1,
            'version_minor' => 1, // avoids unique(class_name, grade, lesson_day, version) conflict with $ownPlan's 1.0.0
        ]);

        $response = $this->actingAs($author)
            ->postJson(route('lesson-plans.retire'), [
                'class_name' => 'Geography',
                'grade'      => 10,
                'lesson_day' => 1,
            ]);

        // Should succeed (author has own plans to archive)
        $response->assertOk();
        $response->assertJson(['success' => true, 'count' => 1]);

        // Author's own plan name should have the suffix appended
        $this->assertStringContainsString('_deleted_', $ownPlan->fresh()->name);

        // Other teacher's plan should be untouched
        $this->assertEquals($otherPlan->name, $otherPlan->fresh()->name);
    }

    // ── Official plan deletion guard ─────────────────────────────────

    #[Test]
    public function deleting_official_plan_returns_error(): void
    {
        $author = User::factory()->create(['email_verified_at' => now()]);

        $officialPlan = LessonPlan::factory()->create([
            'author_id'  => $author->id,
            'is_official' => true,
        ]);

        $response = $this->actingAs($author)
            ->delete(route('lesson-plans.destroy', $officialPlan));

        // Should redirect back with an error flash (not delete the plan)
        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('lesson_plans', ['id' => $officialPlan->id]);
    }

    #[Test]
    public function admin_cannot_delete_official_plan_via_admin_panel(): void
    {
        $admin  = User::factory()->create([
            'email_verified_at' => now(),
            'is_admin'          => true,
        ]);
        $author = User::factory()->create(['email_verified_at' => now()]);

        $officialPlan = LessonPlan::factory()->create([
            'author_id'  => $author->id,
            'is_official' => true,
        ]);

        $response = $this->actingAs($admin)
            ->delete(route('admin.lesson-plans.destroy', $officialPlan));

        $response->assertRedirect(route('admin.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('lesson_plans', ['id' => $officialPlan->id]);
    }

    // ── P0: User deletion cascade guard ───────────────────────────────

    #[Test]
    public function admin_cannot_delete_user_who_owns_official_plan(): void
    {
        $admin  = User::factory()->create([
            'email_verified_at' => now(),
            'is_admin'          => true,
        ]);
        $author = User::factory()->create(['email_verified_at' => now()]);

        $officialPlan = LessonPlan::factory()->create([
            'author_id'  => $author->id,
            'is_official' => true,
        ]);

        $response = $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $author));

        $response->assertRedirect(route('admin.index'));
        $response->assertSessionHas('error');
        // User must still exist — the cascade would have deleted the official plan
        $this->assertDatabaseHas('users', ['id' => $author->id]);
        $this->assertDatabaseHas('lesson_plans', ['id' => $officialPlan->id]);
    }

    #[Test]
    public function admin_bulk_delete_skips_users_owning_official_plans(): void
    {
        $admin   = User::factory()->create([
            'email_verified_at' => now(),
            'is_admin'          => true,
        ]);
        $safeUser    = User::factory()->create(['email_verified_at' => now()]);
        $blockedUser = User::factory()->create(['email_verified_at' => now()]);

        LessonPlan::factory()->create([
            'author_id'   => $blockedUser->id,
            'is_official' => true,
            'lesson_day'  => 97, // unique day to avoid class/grade/day/version collision with safeUser's plan
        ]);
        // $safeUser has no official plans — should be deleted
        LessonPlan::factory()->create([
            'author_id'  => $safeUser->id,
            'lesson_day' => 98, // different day to avoid unique constraint collision
        ]);

        $response = $this->actingAs($admin)
            ->post(route('admin.users.bulk-delete'), [
                'user_ids' => [$safeUser->id, $blockedUser->id],
            ]);

        $response->assertRedirect(route('admin.index'));
        $response->assertSessionHas('success');
        // Safe user deleted; blocked user still exists
        $this->assertDatabaseMissing('users', ['id' => $safeUser->id]);
        $this->assertDatabaseHas('users', ['id' => $blockedUser->id]);
    }

    // ── P1: retireForClassDay missing-file DB desync ───────────────────

    #[Test]
    public function retire_class_day_with_missing_file_does_not_update_file_path(): void
    {
        $author = User::factory()->create(['email_verified_at' => now()]);

        $originalPath = 'lessons/original_file.pdf';
        $plan = LessonPlan::factory()->create([
            'author_id'  => $author->id,
            'class_name' => 'Chemistry',
            'grade'      => 10,
            'lesson_day' => 2,
            'file_path'  => $originalPath,
            'file_name'  => 'original_file.pdf',
        ]);

        // Intentionally do NOT create the file in the fake disk — it is "missing"
        // (Storage::fake('public') is already set up in setUp())

        $response = $this->actingAs($author)
            ->postJson(route('lesson-plans.retire'), [
                'class_name' => 'Chemistry',
                'grade'      => 10,
                'lesson_day' => 2,
            ]);

        $response->assertOk();
        $response->assertJson(['success' => true, 'count' => 1]);

        $refreshed = $plan->fresh();
        // file_path must remain the original — must NOT be rewritten to a renamed path
        $this->assertEquals($originalPath, $refreshed->file_path);
        // name should still get the _deleted_ suffix so the plan is visibly retired
        $this->assertStringContainsString('_deleted_', $refreshed->name);
    }

    // ── F0: store() is_official only for the first plan in a class/day ──

    #[Test]
    public function first_store_for_class_day_is_marked_official(): void
    {
        $author = User::factory()->create(['email_verified_at' => now()]);
        Storage::fake('public');

        $response = $this->actingAs($author)->post(route('lesson-plans.store'), [
            'class_name'  => 'Mathematics',
            'grade'       => 10,
            'lesson_day'  => 5,
            'description' => 'First plan',
            'file'        => \Illuminate\Http\UploadedFile::fake()->create('lesson.docx', 100),
        ]);

        $plan = LessonPlan::where('class_name', 'Mathematics')->where('lesson_day', 5)->first();
        $this->assertNotNull($plan);
        $this->assertTrue((bool) $plan->is_official, 'First upload for a class/day must be Official');
    }

    #[Test]
    public function subsequent_store_for_same_class_day_is_not_marked_official(): void
    {
        $first  = User::factory()->create(['email_verified_at' => now()]);
        $second = User::factory()->create(['email_verified_at' => now()]);
        Storage::fake('public');

        // First plan uploaded by $first — should become Official
        $this->actingAs($first)->post(route('lesson-plans.store'), [
            'class_name'  => 'History',
            'grade'       => 10,
            'lesson_day'  => 3,
            'description' => 'First plan',
            'file'        => \Illuminate\Http\UploadedFile::fake()->create('lesson.docx', 100),
        ]);

        // Second teacher uploads a brand-new plan for the same class/grade/day via store()
        $this->actingAs($second)->post(route('lesson-plans.store'), [
            'class_name'  => 'History',
            'grade'       => 10,
            'lesson_day'  => 3,
            'description' => 'Second plan',
            'file'        => \Illuminate\Http\UploadedFile::fake()->create('lesson2.docx', 100),
        ]);

        $plans = LessonPlan::where('class_name', 'History')->where('lesson_day', 3)->get();
        $this->assertCount(2, $plans);

        $officialPlans = $plans->where('is_official', true);
        // Exactly one official plan — the second upload must NOT override Official
        $this->assertCount(1, $officialPlans, 'Only one plan per class/day may be Official');
        $this->assertEquals($first->id, $officialPlans->first()->author_id, 'The first upload must remain Official');
    }
}
