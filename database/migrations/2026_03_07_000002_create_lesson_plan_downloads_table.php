<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_plan_downloads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();
            // No unique constraint — every download click is a separate row,
            // unlike lesson_plan_engagements which is unique per user/plan/type.
            // No updated_at — append-only event log.
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_plan_downloads');
    }
};
