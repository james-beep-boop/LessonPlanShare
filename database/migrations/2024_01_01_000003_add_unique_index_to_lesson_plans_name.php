<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a unique index on the canonical name column to prevent race-condition
 * duplicates. The application already checks for name collisions before
 * inserting, but without a DB-level constraint, concurrent uploads could
 * still create two records with the same canonical name.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lesson_plans', function (Blueprint $table) {
            $table->unique('name');
        });
    }

    public function down(): void
    {
        Schema::table('lesson_plans', function (Blueprint $table) {
            $table->dropUnique(['name']);
        });
    }
};
