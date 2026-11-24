<?php

namespace App\Providers;

use Illuminate\Database\Events\ConnectionEstablished;
use Illuminate\Database\Events\MigrationStarted;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

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
        Vite::prefetch(concurrency: 3);

        $this->isolatePermissionCacheIfNonMysql();

        Event::listen(ConnectionEstablished::class, fn () => $this->isolatePermissionCacheIfNonMysql());
        Event::listen(MigrationStarted::class, fn () => $this->isolatePermissionCacheIfNonMysql());
        Role::saving(fn () => $this->isolatePermissionCacheIfNonMysql());
        Role::deleting(fn () => $this->isolatePermissionCacheIfNonMysql());
        Role::deleted(fn () => $this->isolatePermissionCacheIfNonMysql());
        Permission::saving(fn () => $this->isolatePermissionCacheIfNonMysql());
        Permission::deleting(fn () => $this->isolatePermissionCacheIfNonMysql());
        Permission::deleted(fn () => $this->isolatePermissionCacheIfNonMysql());

        Gate::before(function ($user, $ability) {
            $this->isolatePermissionCacheIfNonMysql();
            return $user->hasRole('Developer') ? true : null;
        });

        Gate::policy(
            \App\Domain\Equipment\Models\Equipment::class,
            \App\Policies\EquipmentPolicy::class
        );
        Gate::policy(
            \App\Domain\Supplies\Models\Supply::class,
            \App\Policies\SupplyPolicy::class
        );

        \Spatie\Activitylog\Actions\LogActivityAction::beforeLogging(function ($activity) {
            if (empty($activity->ip_address) && request()) {
                $activity->ip_address = request()->ip();
            }
        });

        \Spatie\Activitylog\Models\Activity::creating(function ($activity) {
            if (empty($activity->ip_address) && request()) {
                $activity->ip_address = request()->ip();
            }
        });
    }

    /**
     * Prevent tests or ad-hoc non-MySQL scripts from overwriting the live MySQL permission cache.
     */
    private function isolatePermissionCacheIfNonMysql(): void
    {
        if (!$this->app->runningUnitTests() && config('database.default') === 'mysql') {
            return;
        }

        if (config('cache.default') !== 'array' || config('permission.cache.store') !== 'array') {
            config([
                'cache.default' => 'array',
                'permission.cache.store' => 'array',
            ]);
            app(PermissionRegistrar::class)->initializeCache();
        }
    }
}
