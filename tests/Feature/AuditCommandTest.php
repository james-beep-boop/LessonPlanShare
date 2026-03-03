<?php

namespace Tests\Feature;

use App\Models\LessonPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Feature tests for artisan command audit fixes.
 *
 * Covers:
 *   A7 — lessons:detect-duplicates skips official plans
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
        $nonOfficial = LessonPlan::factory()->create([
            'author_id'   => $author->id,
            'file_hash'   => $hash,
            'is_official' => false,
        ]);

        $official = LessonPlan::factory()->create([
            'author_id'   => $author->id,
            'file_hash'   => $hash,
            'is_official' => true,
        ]);

        $this->artisan('lessons:detect-duplicates')
            ->expectsOutputToContain('is the Official version')
            ->assertSuccessful();

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

        $this->artisan('lessons:detect-duplicates')
            ->assertSuccessful();

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

        $this->artisan('lessons:detect-duplicates', ['--dry-run' => true])
            ->expectsOutputToContain('[DRY RUN]')
            ->assertSuccessful();

        // Both records should still exist
        $this->assertCount(2, LessonPlan::where('file_hash', $hash)->get());
    }

    #[Test]
    public function detect_duplicates_skips_plan_with_dependent_versions(): void
    {
        $author = User::factory()->create();
        $hash   = hash('sha256', 'duplicate-content-with-children');

        // The "duplicate" plan has children pointing to it via parent_id
        $keeper    = LessonPlan::factory()->create([
            'author_id' => $author->id,
            'file_hash' => $hash,
        ]);
        $withChild = LessonPlan::factory()->create([
            'author_id' => $author->id,
            'file_hash' => $hash,
        ]);
        // A child plan that references $withChild as its parent
        LessonPlan::factory()->create([
            'author_id' => $author->id,
            'parent_id' => $withChild->id,
        ]);

        $this->artisan('lessons:detect-duplicates')
            ->expectsOutputToContain('has dependent versions')
            ->assertSuccessful();

        // withChild must not have been deleted (it has dependents)
        $this->assertDatabaseHas('lesson_plans', ['id' => $withChild->id]);
    }
}
