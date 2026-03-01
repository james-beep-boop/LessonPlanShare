<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tracks meaningful engagement by authenticated users with a lesson plan version.
 *
 * "Engagement" means the user has taken one of the following actions:
 *   - Opened the plan in Google Docs viewer ('google_docs')
 *   - Opened the plan in Microsoft Office viewer ('ms_office')
 *   - Downloaded the plan file ('download')
 *
 * This is used to gate voting: a user must have engaged (or be the plan's author)
 * before they can upvote or downvote a version. This replaces the older
 * lesson_plan_views gate (which only required visiting the detail page).
 *
 * Design choices:
 * - Three separate rows per type: a user may engage via multiple methods.
 * - Unique constraint on [user_id, lesson_plan_id, type]: idempotent firstOrCreate().
 * - No updated_at: we only care *whether* engagement happened, not when.
 * - Cascade deletes: removing a user or plan cleans up their engagement records.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_plan_engagements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_plan_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['google_docs', 'ms_office', 'download']);
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['user_id', 'lesson_plan_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_plan_engagements');
    }
};
