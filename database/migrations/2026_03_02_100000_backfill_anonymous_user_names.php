<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Back-fill 'Anonymous' for any users who registered before Teacher Name
 * was a required field, and who have a null or empty name.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where(function ($q) {
                $q->whereNull('name')->orWhere('name', '');
            })
            ->update(['name' => 'Anonymous']);
    }

    public function down(): void
    {
        // Intentional no-op: cannot distinguish 'Anonymous' back-fills
        // from users who genuinely registered with that name.
    }
};
