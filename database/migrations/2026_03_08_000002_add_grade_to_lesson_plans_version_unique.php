<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Updates the semantic-version unique index to include grade.
 *
 * With grade as part of the plan family identity, the same class/day/version
 * can now exist for different grades — they are separate plan families.
 *
 * Before: unique on (class_name, lesson_day, version_major, version_minor, version_patch)
 * After:  unique on (class_name, grade, lesson_day, version_major, version_minor, version_patch)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lesson_plans', function (Blueprint $table) {
            $table->dropUnique('lesson_plans_version_unique');
            $table->unique(
                ['class_name', 'grade', 'lesson_day', 'version_major', 'version_minor', 'version_patch'],
                'lesson_plans_version_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('lesson_plans', function (Blueprint $table) {
            $table->dropUnique('lesson_plans_version_unique');
            $table->unique(
                ['class_name', 'lesson_day', 'version_major', 'version_minor', 'version_patch'],
                'lesson_plans_version_unique'
            );
        });
    }
};
