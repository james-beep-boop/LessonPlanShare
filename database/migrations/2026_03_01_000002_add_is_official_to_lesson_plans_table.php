<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Add is_official flag to lesson_plans.
 *
 * Exactly one plan per (class_name, lesson_day) pair should have is_official = true.
 * The first upload for any class/day (version 1.0.0) is automatically designated
 * "Official" by LessonPlanController::store(). Admins can reassign the flag via
 * AdminController::setOfficial().
 *
 * Back-fill: any existing v1.0.0 plans are marked official on migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lesson_plans', function (Blueprint $table) {
            $table->boolean('is_official')->default(false)->after('vote_score');
        });

        // Back-fill: mark v1.0.0 plans as official (first upload per class/day).
        DB::table('lesson_plans')
            ->where('version_major', 1)
            ->where('version_minor', 0)
            ->where('version_patch', 0)
            ->update(['is_official' => true]);
    }

    public function down(): void
    {
        Schema::table('lesson_plans', function (Blueprint $table) {
            $table->dropColumn('is_official');
        });
    }
};
