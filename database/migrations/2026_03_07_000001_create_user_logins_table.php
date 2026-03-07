<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_logins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();
            // No unique constraint — every login event is its own row.
            // No updated_at — this is an append-only event log.
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_logins');
    }
};
