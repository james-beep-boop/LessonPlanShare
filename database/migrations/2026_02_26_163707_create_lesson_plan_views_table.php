<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tracks which authenticated users have visited the detail page of each lesson plan.
 *
 * This is used to gate voting on the dashboard: a user must have viewed a plan
 * (visited lesson-plans.show) before they can vote on it.
 *
 * Design choices:
 * - No updated_at: we only care whether a view has ever happened, not when it last occurred.
 * - Unique constraint on [user_id, lesson_plan_id]: firstOrCreate() is idempotent.
 * - Cascade deletes: removing a user or plan cleans up their view records.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_plan_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_plan_id')->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['user_id', 'lesson_plan_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_plan_views');
    }
};
