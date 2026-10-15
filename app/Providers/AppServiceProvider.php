<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Owner has full access to all abilities
        Gate::before(function (User $user) {
            if ($user->isOwner()) {
                return true;
            }
        });

        // Register core RBAC and mobile API permission gates
        $permissions = [
            'farmers.view',
            'farmers.create',
            'farmers.update',
            'farmers.delete',
            'farmers.manage',
            'locations.manage',
            'tasks.create',
            'tasks.assign',
            'assignment.create',
            'reports.view',
            'settings.manage',
            'task.view',
            'task.submit',
            'profile.view',
        ];

        foreach ($permissions as $permission) {
            Gate::define($permission, function (User $user) use ($permission) {
                return $user->hasPermission($permission);
            });
        }

        // Allow viewing API documentation
        Gate::define('viewApiDocs', function (?User $user = null) {
            return true;
        });
    }
}
