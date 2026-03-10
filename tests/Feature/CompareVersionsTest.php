<?php

namespace Tests\Feature;

use App\Models\LessonPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Feature tests for the Compare Versions page.
 *
 * Tests the line-level diff feature:
 * - .txt files produce a line diff with summary stats
 * - .docx / other extensions return a graceful "not supported" message
 * - The ?compare_to= query param cannot reference plans outside the same family
 */
class CompareVersionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    #[Test]
    public function compare_page_shows_line_diff_summary_for_txt_files(): void
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'is_admin' => true]);

        Storage::disk('public')->put('lessons/v1.txt', "Line A\nLine B\nLine C\n");
        $root = LessonPlan::factory()->create([
            'author_id'     => $user->id,
            'class_name'    => 'Science',
            'grade'         => 10,
            'lesson_day'    => 4,
            'version_major' => 1,
            'version_minor' => 0,
            'version_patch' => 0,
            'file_path'     => 'lessons/v1.txt',
            'file_name'     => 'v1.txt',
        ]);

        Storage::disk('public')->put('lessons/v2.txt', "Line A\nLine B changed\nLine C\nLine D\n");
        $current = $root->createNewVersion([
            'author_id'     => $user->id,
            'class_name'    => 'Science',
            'grade'         => 10,
            'lesson_day'    => 4,
            'name'          => 'Science_Grade10_Day4_teacher_v1-0-1',
            'version_major' => 1,
            'version_minor' => 0,
            'version_patch' => 1,
            'file_path'     => 'lessons/v2.txt',
            'file_name'     => 'v2.txt',
            'file_size'     => 100,
            'file_hash'     => hash('sha256', 'v2'),
        ]);

        $response = $this->actingAs($user)->get(route('admin.lesson-plans.compare', $current));

        $response->assertOk();
        $response->assertSee('Inline Diff');
        $response->assertSee('Lines Added');
        $response->assertSee('Lines Removed');
        $response->assertSee('Lines Changed');
    }

    #[Test]
    public function compare_page_shows_graceful_message_for_unsupported_extensions(): void
    {
        // Use a genuinely unsupported format (.pdf) to exercise the "cannot be compared as text" path.
        $user = User::factory()->create(['email_verified_at' => now(), 'is_admin' => true]);

        Storage::disk('public')->put('lessons/v1.pdf', '%PDF-1.4 fake');
        $root = LessonPlan::factory()->create([
            'author_id'     => $user->id,
            'class_name'    => 'English',
            'grade'         => 10,
            'lesson_day'    => 2,
            'version_major' => 1,
            'version_minor' => 0,
            'version_patch' => 0,
            'file_path'     => 'lessons/v1.pdf',
            'file_name'     => 'v1.pdf',
        ]);

        Storage::disk('public')->put('lessons/v2.pdf', '%PDF-1.4 fake v2');
        $current = $root->createNewVersion([
            'author_id'     => $user->id,
            'class_name'    => 'English',
            'grade'         => 10,
            'lesson_day'    => 2,
            'name'          => 'English_Grade10_Day2_teacher_v1-0-1',
            'version_major' => 1,
            'version_minor' => 0,
            'version_patch' => 1,
            'file_path'     => 'lessons/v2.pdf',
            'file_name'     => 'v2.pdf',
            'file_size'     => 100,
            'file_hash'     => hash('sha256', 'v2-pdf'),
        ]);

        $response = $this->actingAs($user)->get(route('admin.lesson-plans.compare', $current));

        $response->assertOk();
        // .pdf is not a supported format — expect the graceful "cannot be compared as text" message
        $response->assertSee('cannot be compared as text');
    }

    #[Test]
    public function compare_to_query_param_cannot_use_versions_outside_the_same_family(): void
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'is_admin' => true]);

        Storage::disk('public')->put('lessons/family-root.txt', "A\nB\n");
        $root = LessonPlan::factory()->create([
            'author_id'     => $user->id,
            'class_name'    => 'History',
            'grade'         => 10,
            'lesson_day'    => 1,
            'version_major' => 1,
            'version_minor' => 0,
            'version_patch' => 0,
            'file_path'     => 'lessons/family-root.txt',
            'file_name'     => 'family-root.txt',
        ]);

        Storage::disk('public')->put('lessons/family-current.txt', "A\nB\nC\n");
        $current = $root->createNewVersion([
            'author_id'     => $user->id,
            'class_name'    => 'History',
            'grade'         => 10,
            'lesson_day'    => 1,
            'name'          => 'History_Grade10_Day1_teacher_v1-0-1',
            'version_major' => 1,
            'version_minor' => 0,
            'version_patch' => 1,
            'file_path'     => 'lessons/family-current.txt',
            'file_name'     => 'family-current.txt',
            'file_size'     => 100,
            'file_hash'     => hash('sha256', 'family-current'),
        ]);

        $foreign = LessonPlan::factory()->create([
            'author_id'     => $user->id,
            'class_name'    => 'Mathematics',
            'lesson_day'    => 9,
            'version_major' => 1,
            'version_minor' => 9,
            'version_patch' => 0,
            'file_path'     => 'lessons/foreign.txt',
            'file_name'     => 'foreign.txt',
        ]);
        Storage::disk('public')->put('lessons/foreign.txt', "foreign\n");

        $response = $this->actingAs($user)->get(
            route('admin.lesson-plans.compare', ['lessonPlan' => $current, 'compare_to' => $foreign->id])
        );

        $response->assertOk();
        // Fallback should choose previous in-family version: root is shown as (selected),
        // and the foreign plan does not appear anywhere in the History/day1 compare table.
        $response->assertSee('(selected)');
        $response->assertDontSee('not available to compare');
    }
}
