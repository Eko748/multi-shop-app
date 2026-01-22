<?php

use App\Http\Middleware\RedirectIfAuthenticated;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        apiPrefix: 'api',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'tamu' => RedirectIfAuthenticated::class
        ]);

        $middleware->statefulApi();

        $middleware->use([
            \App\Http\Middleware\LogLastActivity::class,
            \App\Http\Middleware\LogUserLogout::class,
            \App\Http\Middleware\ShareMenuPermissions::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Illuminate\Database\QueryException $e, $request) {
            if (str_contains($e->getMessage(), '[2002]')) {
                return redirect()->route('login');
            }
        });

        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            return redirect()->route('login');
        });
    })
    ->withProviders([
        \App\Providers\ViewServiceProvider::class,
    ])->create();
