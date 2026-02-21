<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('votes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lesson_plan_id');
            $table->unsignedBigInteger('user_id');
            $table->tinyInteger('value'); // +1 for upvote, -1 for downvote
            $table->timestamps();

            // Each user can vote on each version exactly once
            $table->unique(['lesson_plan_id', 'user_id']);

            $table->foreign('lesson_plan_id')->references('id')->on('lesson_plans')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('votes');
    }
};
