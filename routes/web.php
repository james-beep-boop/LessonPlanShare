<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LessonPlanController;
use App\Http\Controllers\VoteController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
| The dashboard (browse all plans) is publicly visible.
| Individual plan pages are also public so non-registered visitors can browse.
*/

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

Route::get('/stats', [DashboardController::class, 'stats'])->name('stats');

Route::get('/lesson-plans/{lessonPlan}', [LessonPlanController::class, 'show'])
    ->name('lesson-plans.show');

Route::get('/lesson-plans/{lessonPlan}/preview', [LessonPlanController::class, 'preview'])
    ->name('lesson-plans.preview');

Route::get('/lesson-plans/{lessonPlan}/download', [LessonPlanController::class, 'download'])
    ->name('lesson-plans.download');

/*
|--------------------------------------------------------------------------
| Authenticated + Email-Verified Routes
|--------------------------------------------------------------------------
| These require the user to be logged in AND to have verified their email
| address (clicked the confirmation link in the sign-up email).
*/

Route::middleware(['auth', 'verified'])->group(function () {

    // My Plans
    Route::get('/my-plans', [LessonPlanController::class, 'myPlans'])
        ->name('my-plans');

    // Create new plan
    Route::get('/lesson-plans-create', [LessonPlanController::class, 'create'])
        ->name('lesson-plans.create');
    Route::post('/lesson-plans', [LessonPlanController::class, 'store'])
        ->name('lesson-plans.store');

    // Create new version of an existing plan
    Route::get('/lesson-plans/{lessonPlan}/new-version', [LessonPlanController::class, 'edit'])
        ->name('lesson-plans.new-version');
    Route::put('/lesson-plans/{lessonPlan}', [LessonPlanController::class, 'update'])
        ->name('lesson-plans.update');

    // Delete (author only, enforced in controller)
    Route::delete('/lesson-plans/{lessonPlan}', [LessonPlanController::class, 'destroy'])
        ->name('lesson-plans.destroy');

    // Voting (upvote / downvote)
    Route::post('/lesson-plans/{lessonPlan}/vote', [VoteController::class, 'store'])
        ->name('votes.store');
});

/*
|--------------------------------------------------------------------------
| Auth Routes (provided by Laravel Breeze)
|--------------------------------------------------------------------------
| Breeze adds login, register, password reset, and email verification
| routes in routes/auth.php.
*/
require __DIR__ . '/auth.php';
