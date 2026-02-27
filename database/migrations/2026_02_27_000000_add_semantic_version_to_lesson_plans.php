<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds semantic versioning columns to lesson_plans.
 *
 * Semantic version format: {major}.{minor}.{patch} (e.g. "1.2.16")
 *
 * Rules:
 * - The first integer (major) is always 1 in this system.
 * - A "major revision" bumps the second integer and resets the third.
 * - A "minor revision" bumps only the third integer.
 * - Versions are assigned GLOBALLY per (class_name, lesson_day) pair,
 *   not per family — so all uploads for the same class/day share one
 *   version sequence.
 * - A unique index on (class_name, lesson_day, version_major, version_minor,
 *   version_patch) enforces uniqueness at the DB level.
 *
 * Backfill strategy for existing data:
 * - Group plans by (class_name, lesson_day), sort by id (creation order).
 * - Assign 1.0.0, 1.1.0, 1.2.0 … within each group.
 * - This treats every pre-existing integer version as a "major" revision
 *   in semantic terms, preserving relative ordering.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Step 1: Add the three new columns ──
        Schema::table('lesson_plans', function (Blueprint $table) {
            $table->unsignedTinyInteger('version_major')->default(1)->after('version_number');
            $table->unsignedSmallInteger('version_minor')->default(0)->after('version_major');
            $table->unsignedSmallInteger('version_patch')->default(0)->after('version_minor');
        });

        // ── Step 2: Backfill — assign semantic versions ──
        // Get every distinct (class_name, lesson_day) group that has at least one plan.
        $groups = DB::table('lesson_plans')
            ->select('class_name', 'lesson_day')
            ->groupBy('class_name', 'lesson_day')
            ->get();

        foreach ($groups as $group) {
            // Fetch all plan IDs for this class/day in creation order.
            $ids = DB::table('lesson_plans')
                ->where('class_name', $group->class_name)
                ->where('lesson_day', $group->lesson_day)
                ->orderBy('id')
                ->pluck('id');

            // Assign 1.0.0, 1.1.0, 1.2.0 … sequentially.
            foreach ($ids as $index => $id) {
                DB::table('lesson_plans')->where('id', $id)->update([
                    'version_major' => 1,
                    'version_minor' => $index,
                    'version_patch' => 0,
                ]);
            }
        }

        // ── Step 3: Add unique index AFTER backfill ──
        // The backfill guarantees no duplicates exist at this point.
        Schema::table('lesson_plans', function (Blueprint $table) {
            $table->unique(
                ['class_name', 'lesson_day', 'version_major', 'version_minor', 'version_patch'],
                'lesson_plans_version_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('lesson_plans', function (Blueprint $table) {
            $table->dropUnique('lesson_plans_version_unique');
            $table->dropColumn(['version_major', 'version_minor', 'version_patch']);
        });
    }
};
