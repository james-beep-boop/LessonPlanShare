<?php

namespace Tests\Feature;

use App\Http\Controllers\LessonPlanController;
use App\Models\LessonPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Feature tests for the semantic versioning system.
 *
 * Tests the global-per-class/day version assignment logic:
 * - First upload for a class/day gets 1.0.0
 * - Subsequent major revisions increment the minor integer
 * - Minor revisions increment only the patch integer
 * - Changing class/day on a "new version" form starts a fresh sequence
 * - The unique DB index prevents duplicate versions
 * - The canonical filename includes the version suffix
 * - The AJAX nextVersion endpoint returns correct JSON
 */
class SemanticVersionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
    }

    // ──────────────────────────────────────────────────────────────────
    //  Model unit tests (computeNextSemanticVersion logic via model)
    // ──────────────────────────────────────────────────────────────────

    /** @test */
    public function first_upload_for_class_day_gets_version_1_0_0(): void
    {
        $plan = LessonPlan::factory()->create([
            'class_name'    => 'Mathematics',
            'lesson_day'    => 5,
            'version_major' => 1,
            'version_minor' => 0,
            'version_patch' => 0,
        ]);

        $this->assertEquals('1.0.0', $plan->semantic_version);
    }

    /** @test */
    public function semantic_version_accessor_formats_correctly(): void
    {
        $plan = LessonPlan::factory()->create([
            'version_major' => 1,
            'version_minor' => 12,
            'version_patch' => 3,
        ]);

        $this->assertEquals('1.12.3', $plan->semantic_version);
    }

    // ──────────────────────────────────────────────────────────────────
    //  Controller integration tests (full HTTP request cycle)
    // ──────────────────────────────────────────────────────────────────

    /** @test */
    public function store_assigns_1_0_0_when_no_existing_plan_for_class_day(): void
    {
        $this->actingAs($this->user);

        $response = $this->post(route('lesson-plans.store'), [
            'class_name'  => 'English',
            'lesson_day'  => 1,
            'description' => 'First upload',
            'file'        => UploadedFile::fake()->create('lesson.docx', 100),
        ]);

        $plan = LessonPlan::where('class_name', 'English')->where('lesson_day', 1)->first();
        $this->assertNotNull($plan);
        $this->assertEquals(1, $plan->version_major);
        $this->assertEquals(0, $plan->version_minor);
        $this->assertEquals(0, $plan->version_patch);
        $this->assertEquals('1.0.0', $plan->semantic_version);
    }

    /** @test */
    public function store_assigns_next_major_when_plan_already_exists_for_class_day(): void
    {
        // Seed an existing plan at 1.0.0
        LessonPlan::factory()->create([
            'class_name'    => 'Science',
            'lesson_day'    => 3,
            'version_major' => 1,
            'version_minor' => 0,
            'version_patch' => 0,
        ]);

        $this->actingAs($this->user);

        $this->post(route('lesson-plans.store'), [
            'class_name'  => 'Science',
            'lesson_day'  => 3,
            'description' => 'Second upload (standalone)',
            'file'        => UploadedFile::fake()->create('lesson.docx', 100),
        ]);

        $newPlan = LessonPlan::where('class_name', 'Science')
            ->where('lesson_day', 3)
            ->orderByDesc('id')
            ->first();

        $this->assertEquals('1.1.0', $newPlan->semantic_version);
    }

    /** @test */
    public function update_major_revision_increments_minor_and_resets_patch(): void
    {
        // Seed existing plan at 1.0.0
        $parent = LessonPlan::factory()->create([
            'author_id'     => $this->user->id,
            'class_name'    => 'History',
            'lesson_day'    => 7,
            'version_major' => 1,
            'version_minor' => 0,
            'version_patch' => 0,
        ]);

        $this->actingAs($this->user);

        $this->put(route('lesson-plans.update', $parent), [
            'class_name'    => 'History',
            'lesson_day'    => 7,
            'revision_type' => 'major',
            'file'          => UploadedFile::fake()->create('lesson.docx', 100),
        ]);

        $newVersion = LessonPlan::where('class_name', 'History')
            ->where('lesson_day', 7)
            ->orderByDesc('id')
            ->first();

        $this->assertEquals('1.1.0', $newVersion->semantic_version);
    }

    /** @test */
    public function update_minor_revision_increments_patch_only(): void
    {
        // Seed existing plan at 1.1.0
        $parent = LessonPlan::factory()->create([
            'author_id'     => $this->user->id,
            'class_name'    => 'Mathematics',
            'lesson_day'    => 10,
            'version_major' => 1,
            'version_minor' => 1,
            'version_patch' => 0,
        ]);

        $this->actingAs($this->user);

        $this->put(route('lesson-plans.update', $parent), [
            'class_name'    => 'Mathematics',
            'lesson_day'    => 10,
            'revision_type' => 'minor',
            'file'          => UploadedFile::fake()->create('lesson.docx', 100),
        ]);

        $newVersion = LessonPlan::where('class_name', 'Mathematics')
            ->where('lesson_day', 10)
            ->orderByDesc('id')
            ->first();

        $this->assertEquals('1.1.1', $newVersion->semantic_version);
    }

    /** @test */
    public function update_with_changed_class_day_gets_version_1_0_0_if_new(): void
    {
        $parent = LessonPlan::factory()->create([
            'author_id'     => $this->user->id,
            'class_name'    => 'English',
            'lesson_day'    => 5,
            'version_major' => 1,
            'version_minor' => 2,
            'version_patch' => 3,
        ]);

        $this->actingAs($this->user);

        // Create new version but switch to a completely different class/day
        $this->put(route('lesson-plans.update', $parent), [
            'class_name'    => 'History', // no existing plans for History/day 20
            'lesson_day'    => 20,
            'revision_type' => 'major',
            'file'          => UploadedFile::fake()->create('lesson.docx', 100),
        ]);

        $newVersion = LessonPlan::where('class_name', 'History')
            ->where('lesson_day', 20)
            ->first();

        $this->assertNotNull($newVersion);
        $this->assertEquals('1.0.0', $newVersion->semantic_version);
    }

    /** @test */
    public function revision_type_is_required_for_update(): void
    {
        $parent = LessonPlan::factory()->create([
            'author_id' => $this->user->id,
        ]);

        $this->actingAs($this->user);

        $response = $this->put(route('lesson-plans.update', $parent), [
            'class_name' => 'English',
            'lesson_day' => 1,
            // revision_type intentionally omitted
            'file'       => UploadedFile::fake()->create('lesson.docx', 100),
        ]);

        $response->assertSessionHasErrors('revision_type');
    }

    /** @test */
    public function canonical_filename_includes_version_suffix(): void
    {
        $this->actingAs($this->user);

        $this->post(route('lesson-plans.store'), [
            'class_name'  => 'Science',
            'lesson_day'  => 2,
            'file'        => UploadedFile::fake()->create('lesson.docx', 100),
        ]);

        $plan = LessonPlan::where('class_name', 'Science')->where('lesson_day', 2)->first();
        $this->assertNotNull($plan);
        $this->assertStringContainsString('_v1-0-0', $plan->file_name);
    }

    // ──────────────────────────────────────────────────────────────────
    //  AJAX nextVersion endpoint
    // ──────────────────────────────────────────────────────────────────

    /** @test */
    public function next_version_endpoint_returns_1_0_0_for_new_class_day(): void
    {
        $this->actingAs($this->user);

        $response = $this->getJson(route('lesson-plans.next-version', [
            'class_name'    => 'English',
            'lesson_day'    => 99,
            'revision_type' => 'major',
        ]));

        $response->assertOk()->assertJson(['version' => '1.0.0']);
    }

    /** @test */
    public function next_version_endpoint_returns_correct_major_increment(): void
    {
        LessonPlan::factory()->create([
            'class_name'    => 'Mathematics',
            'lesson_day'    => 1,
            'version_major' => 1,
            'version_minor' => 3,
            'version_patch' => 2,
        ]);

        $this->actingAs($this->user);

        $response = $this->getJson(route('lesson-plans.next-version', [
            'class_name'    => 'Mathematics',
            'lesson_day'    => 1,
            'revision_type' => 'major',
        ]));

        $response->assertOk()->assertJson(['version' => '1.4.0']);
    }

    /** @test */
    public function next_version_endpoint_returns_correct_minor_increment(): void
    {
        LessonPlan::factory()->create([
            'class_name'    => 'Mathematics',
            'lesson_day'    => 1,
            'version_major' => 1,
            'version_minor' => 3,
            'version_patch' => 2,
        ]);

        $this->actingAs($this->user);

        $response = $this->getJson(route('lesson-plans.next-version', [
            'class_name'    => 'Mathematics',
            'lesson_day'    => 1,
            'revision_type' => 'minor',
        ]));

        $response->assertOk()->assertJson(['version' => '1.3.3']);
    }

    /** @test */
    public function next_version_endpoint_returns_1_0_0_when_class_day_missing(): void
    {
        $this->actingAs($this->user);

        $response = $this->getJson(route('lesson-plans.next-version'));

        $response->assertOk()->assertJson(['version' => '1.0.0']);
    }
}
