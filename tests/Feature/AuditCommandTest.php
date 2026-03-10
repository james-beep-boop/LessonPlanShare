<?php

namespace Tests\Feature;

use App\Models\LessonPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Feature tests for artisan command audit fixes.
 *
 * Covers:
 *   A7 — lessons:detect-duplicates skips official plans
 *
 * Uses Artisan::call() + Artisan::output() instead of the PendingCommand
 * ->expectsOutputToContain() fluent API. The PendingCommand mock intercepts
 * output in an interactive mode that can block when warn() writes to the
 * command's error channel rather than stdout.
 */
class AuditCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Mail::fake();
    }

    // ── A7: Dedup command protects official plans ──────────────────────

    #[Test]
    public function detect_duplicates_skips_official_plans(): void
    {
        $author = User::factory()->create();
        $hash   = hash('sha256', 'duplicate-content-official');

        // Two plans with identical hashes; the second one is Official
        LessonPlan::factory()->create([
            'author_id'   => $author->id,
            'file_hash'   => $hash,
            'is_official' => false,
        ]);

        $official = LessonPlan::factory()->create([
            'author_id'   => $author->id,
            'file_hash'   => $hash,
            'is_official' => true,
        ]);

        $exitCode = Artisan::call('lessons:detect-duplicates');
        $output   = Artisan::output();

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('is the Official version', $output);

        // Official plan must still exist
        $this->assertDatabaseHas('lesson_plans', ['id' => $official->id]);
    }

    #[Test]
    public function detect_duplicates_removes_non_official_duplicate_keeping_earliest(): void
    {
        $author = User::factory()->create();
        $hash   = hash('sha256', 'duplicate-content-non-official');

        // Create keeper (lower id = earlier) then duplicate
        $keeper    = LessonPlan::factory()->create([
            'author_id'   => $author->id,
            'file_hash'   => $hash,
            'is_official' => false,
        ]);
        $duplicate = LessonPlan::factory()->create([
            'author_id'   => $author->id,
            'file_hash'   => $hash,
            'is_official' => false,
        ]);

        $exitCode = Artisan::call('lessons:detect-duplicates');

        $this->assertEquals(0, $exitCode);
        $this->assertDatabaseHas('lesson_plans', ['id' => $keeper->id]);
        $this->assertDatabaseMissing('lesson_plans', ['id' => $duplicate->id]);
    }

    #[Test]
    public function detect_duplicates_dry_run_does_not_delete_anything(): void
    {
        $author = User::factory()->create();
        $hash   = hash('sha256', 'duplicate-content-dryrun');

        LessonPlan::factory()->create(['author_id' => $author->id, 'file_hash' => $hash]);
        LessonPlan::factory()->create(['author_id' => $author->id, 'file_hash' => $hash]);

        $exitCode = Artisan::call('lessons:detect-duplicates', ['--dry-run' => true]);
        $output   = Artisan::output();

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('[DRY RUN]', $output);

        // Both records should still exist
        $this->assertCount(2, LessonPlan::where('file_hash', $hash)->get());
    }

    #[Test]
    public function detect_duplicates_skips_plan_with_dependent_versions(): void
    {
        $author = User::factory()->create();
        $hash   = hash('sha256', 'duplicate-content-with-children');

        // The "duplicate" plan has children pointing to it via parent_id.
        // Use explicit unique lesson_days to avoid hitting the unique(class,grade,day,version) constraint.
        LessonPlan::factory()->create([
            'author_id'  => $author->id,
            'file_hash'  => $hash,
            'lesson_day' => 91,
        ]);
        $withChild = LessonPlan::factory()->create([
            'author_id'  => $author->id,
            'file_hash'  => $hash,
            'lesson_day' => 92,
        ]);
        // A child plan that references $withChild as its parent
        LessonPlan::factory()->create([
            'author_id'  => $author->id,
            'parent_id'  => $withChild->id,
            'lesson_day' => 93,
        ]);

        $exitCode = Artisan::call('lessons:detect-duplicates');
        $output   = Artisan::output();

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('has dependent versions', $output);

        // withChild must not have been deleted (it has dependents)
        $this->assertDatabaseHas('lesson_plans', ['id' => $withChild->id]);
    }
}
