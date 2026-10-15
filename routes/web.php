<?php

use App\Http\Controllers\Admin\AuditController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FarmerController;
use App\Http\Controllers\Admin\FarmLocationController;
use App\Http\Controllers\Admin\MonitoringController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\TaskAssignmentController;
use App\Http\Controllers\Admin\TaskTemplateController;
use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

// Redirect root to dashboard (if authenticated) or login
Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('admin.dashboard')
        : redirect()->route('login');
});

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('admin.logout');

// Protected Admin Routes
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth'])
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::resource('farmers', FarmerController::class);
        Route::resource('locations', FarmLocationController::class);
        Route::post('locations/{location}/farmers', [FarmLocationController::class, 'attachFarmer'])->name('locations.farmers.attach');
        Route::delete('locations/{location}/farmers/{farmer}', [FarmLocationController::class, 'detachFarmer'])->name('locations.farmers.detach');
        Route::resource('tasks', TaskTemplateController::class);
        Route::resource('assignments', TaskAssignmentController::class);
        Route::post('assignments/{assignment}/review', [TaskAssignmentController::class, 'reviewSubmission'])->name('assignments.review');
        Route::get('/monitoring', [MonitoringController::class, 'index'])->name('monitoring');
        Route::get('/reports', [ReportController::class, 'index'])->name('reports');
        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications');
        Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
        Route::delete('/notifications/{id}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
        Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
        Route::get('/audit', [AuditController::class, 'index'])->name('audit');
    });

// Swagger UI API Documentation (powered by Scramble OpenAPI spec)
Route::get('/api/documentation', function () {
    return view('swagger');
})->name('api.documentation');
