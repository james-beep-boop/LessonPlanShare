<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_plans', function (Blueprint $table) {
            $table->id();

            // Structured naming fields — these generate the canonical name
            $table->string('class_name');       // e.g., "AP Biology", "Algebra II"
            $table->unsignedInteger('lesson_day'); // e.g., 1, 2, 15
            $table->text('description')->nullable();

            // The canonical name is auto-generated:
            // "{class_name}_Day{lesson_day}_{author_name}_{UTC timestamp}"
            // Stored for display and searching.
            $table->string('name');

            // Versioning: original_id links to the root plan in a family.
            // parent_id links to the immediate predecessor version.
            // For the very first upload, both are NULL.
            $table->unsignedBigInteger('original_id')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->unsignedInteger('version_number')->default(1);

            $table->unsignedBigInteger('author_id');
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();          // original uploaded filename
            $table->unsignedInteger('file_size')->nullable();  // bytes
            $table->string('file_hash', 64)->nullable();        // SHA-256 hash for duplicate content detection

            // Cached vote tally (upvotes minus downvotes) for fast sorting
            $table->integer('vote_score')->default(0);

            $table->timestamps();

            // Indexes for common queries
            $table->index('class_name');
            $table->index('original_id');
            $table->index('parent_id');
            $table->index('vote_score');
            $table->index('file_hash');

            $table->foreign('author_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('original_id')->references('id')->on('lesson_plans')->onDelete('set null');
            $table->foreign('parent_id')->references('id')->on('lesson_plans')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_plans');
    }
};
