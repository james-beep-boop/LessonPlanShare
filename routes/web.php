<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LessonPlanController;
use App\Http\Controllers\VoteController;
use App\Http\Middleware\AdminMiddleware;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
| The dashboard is publicly visible — no login required.
*/

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// Resend verification email — no auth required (the send-verification route is public
// so the admin debug button works without needing the caller to be signed in)
Route::post('/users/{user}/send-verification', [DashboardController::class, 'sendVerification'])
    ->name('users.send-verification');

/*
|--------------------------------------------------------------------------
| Authenticated + Email-Verified Routes
|--------------------------------------------------------------------------
| These require the user to be logged in AND to have verified their email.
*/

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/stats', [DashboardController::class, 'stats'])->name('stats');

    // View, preview, and download require a verified account
    Route::get('/lesson-plans/{lessonPlan}', [LessonPlanController::class, 'show'])
        ->name('lesson-plans.show');
    Route::get('/lesson-plans/{lessonPlan}/preview', [LessonPlanController::class, 'preview'])
        ->name('lesson-plans.preview');
    Route::get('/lesson-plans/{lessonPlan}/download', [LessonPlanController::class, 'download'])
        ->name('lesson-plans.download');

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
| Admin Routes
|--------------------------------------------------------------------------
| Require auth + verified + is_admin. AdminMiddleware enforces the flag.
*/

Route::middleware(['auth', 'verified', AdminMiddleware::class])->prefix('admin')->group(function () {

    Route::get('/', [AdminController::class, 'index'])->name('admin.index');

    // Lesson plan management
    Route::delete('/lesson-plans/{lessonPlan}', [AdminController::class, 'destroyPlan'])
        ->name('admin.lesson-plans.destroy');
    Route::post('/lesson-plans/bulk-delete', [AdminController::class, 'bulkDestroyPlans'])
        ->name('admin.lesson-plans.bulk-delete');

    // User management
    Route::delete('/users/{user}', [AdminController::class, 'destroyUser'])
        ->name('admin.users.destroy');
    Route::post('/users/bulk-delete', [AdminController::class, 'bulkDestroyUsers'])
        ->name('admin.users.bulk-delete');
    Route::post('/users/{user}/toggle-admin', [AdminController::class, 'toggleAdmin'])
        ->name('admin.users.toggle-admin');

});

/*
|--------------------------------------------------------------------------
| Auth Routes (provided by Laravel Breeze)
|--------------------------------------------------------------------------
*/
require __DIR__ . '/auth.php';
