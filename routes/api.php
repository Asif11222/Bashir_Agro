<?php

use App\Http\Controllers\Api\V1\AssignmentController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DashboardApiController;
use App\Http\Controllers\Api\V1\FarmApiController;
use App\Http\Controllers\Api\V1\FarmerController;
use App\Http\Controllers\Api\V1\LocationController;
use App\Http\Controllers\Api\V1\SubmissionController;
use App\Http\Controllers\Api\V1\TaskController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes (Bashir Agro V1)
|--------------------------------------------------------------------------
|
| Versioned REST API endpoints for Flutter mobile application and
| backend integrations. Protected with Laravel Sanctum authentication.
|
*/

Route::prefix('v1')->group(function () {
    // Public Authentication
    Route::post('/auth/login', [AuthController::class, 'login'])->name('api.v1.auth.login');

    // Authenticated Endpoints (Sanctum)
    Route::middleware('auth:sanctum')->group(function () {
        // User & Session
        Route::get('/auth/me', [AuthController::class, 'me'])->name('api.v1.auth.me');
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('api.v1.auth.logout');

        // Owner / Admin Dashboard Metrics
        Route::get('/owner/dashboard', [DashboardApiController::class, 'summary'])->name('api.v1.owner.dashboard');

        // Farmers List & Profiles
        Route::get('/farmers', [FarmerController::class, 'index'])->name('api.v1.farmers.index');
        Route::get('/farmer/profile', [FarmerController::class, 'profile'])->name('api.v1.farmer.profile');
        Route::get('/farmers/{farmer}', [FarmerController::class, 'show'])->name('api.v1.farmers.show');

        // Tasks Templates
        Route::get('/tasks', [TaskController::class, 'index'])->name('api.v1.tasks.index');
        Route::get('/tasks/{task}', [TaskController::class, 'show'])->name('api.v1.tasks.show');

        // Task Assignments
        Route::get('/assignments', [AssignmentController::class, 'index'])->name('api.v1.assignments.index');
        Route::get('/assignments/{assignment}', [AssignmentController::class, 'show'])->name('api.v1.assignments.show');
        Route::post('/assignments/{assignment}/start', [AssignmentController::class, 'start'])->name('api.v1.assignments.start');

        // Submissions & Evidence Upload
        Route::post('/assignments/{assignment}/submit', [SubmissionController::class, 'submit'])->name('api.v1.submissions.submit');
        Route::get('/assignments/{assignment}/submission', [SubmissionController::class, 'show'])->name('api.v1.submissions.show');

        // Farm Locations
        Route::get('/locations', [LocationController::class, 'index'])->name('api.v1.locations.index');
        Route::get('/locations/{location}', [LocationController::class, 'show'])->name('api.v1.locations.show');

        // Field Boundary Management (Phase 12A V2)
        Route::get('/farms', [FarmApiController::class, 'index'])->name('api.v1.farms.index');
        Route::get('/farms/{id}', [FarmApiController::class, 'show'])->name('api.v1.farms.show');
        Route::post('/farms', [FarmApiController::class, 'store'])->name('api.v1.farms.store');
        Route::put('/farms/{id}', [FarmApiController::class, 'update'])->name('api.v1.farms.update');
    });
});
