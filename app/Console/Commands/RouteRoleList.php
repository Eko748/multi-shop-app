<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class RouteRoleList extends Command
{
    protected $signature = 'route:role-list';
    protected $description = 'List all routes and their allowed roles from middleware';

    public function handle()
    {
        $routes = Route::getRoutes();

        $this->info(str_pad('METHOD', 10) . str_pad('URI', 40) . 'ROLES');

        foreach ($routes as $route) {
            $middleware = $route->gatherMiddleware(); // Laravel 11 compatible
            $roles = [];

            foreach ($middleware as $m) {
                if (Str::startsWith($m, 'role:')) {
                    $roleStr = Str::after($m, 'role:');
                    $roles = explode(',', $roleStr);
                }
            }

            if (!empty($roles)) {
                $this->line(str_pad(implode(',', $route->methods()), 10) .
                            str_pad($route->uri(), 40) .
                            implode(', ', $roles));
            }
        }

        return Command::SUCCESS;
    }
}
