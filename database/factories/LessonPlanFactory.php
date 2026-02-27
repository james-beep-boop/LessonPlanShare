<?php

namespace Database\Factories;

use App\Models\LessonPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<LessonPlan>
 */
class LessonPlanFactory extends Factory
{
    protected $model = LessonPlan::class;

    public function definition(): array
    {
        $className  = $this->faker->randomElement(['English', 'History', 'Mathematics', 'Science']);
        $lessonDay  = $this->faker->numberBetween(1, 20);
        $authorName = $this->faker->userName();
        $ts         = Carbon::now('UTC')->format('Ymd_His');
        $name       = "{$className}_Day{$lessonDay}_{$authorName}_{$ts}UTC_v1-0-0";

        return [
            'class_name'     => $className,
            'lesson_day'     => $lessonDay,
            'description'    => $this->faker->optional()->sentence(),
            'name'           => $name,
            'original_id'    => null,
            'parent_id'      => null,
            'version_number' => 1,
            'version_major'  => 1,
            'version_minor'  => 0,
            'version_patch'  => 0,
            'author_id'      => User::factory(),
            'file_path'      => 'lessons/' . $name . '.docx',
            'file_name'      => $name . '.docx',
            'file_size'      => $this->faker->numberBetween(10000, 500000),
            'file_hash'      => $this->faker->sha256(),
            'vote_score'     => 0,
        ];
    }
}
