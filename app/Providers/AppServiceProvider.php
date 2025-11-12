<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Middleware\PermissionMiddleware as MiddlewarePermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware as MiddlewareRoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware as MiddlewareRoleOrPermissionMiddleware;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Tempat registrasi service binding, jika perlu
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $router = $this->app['router'];

        $router->aliasMiddleware('permission', MiddlewarePermissionMiddleware::class);
        $router->aliasMiddleware('role', MiddlewareRoleMiddleware::class);
        $router->aliasMiddleware('role_or_permission', MiddlewareRoleOrPermissionMiddleware::class);
    }
}
