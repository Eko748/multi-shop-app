<?php

use App\Http\Middleware\RedirectIfAuthenticated;
use App\Http\Middleware\VerifyApiKey;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Session\TokenMismatchException;

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
            'tamu' => RedirectIfAuthenticated::class,
            'verify.apikey' => VerifyApiKey::class,
            'ensure.toko' => \App\Http\Middleware\EnsureTokoIsSelected::class,
        ]);

        $middleware->statefulApi();

        $middleware->use([
            \App\Http\Middleware\LogLastActivity::class,
            \App\Http\Middleware\LogUserLogout::class,
            \App\Http\Middleware\ShareMenuPermissions::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\SetActiveTokoContext::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'cancel-login',
            'post-cancel-login',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {

        $exceptions->render(function (TokenMismatchException $e, $request) {
            return response()->json([
                'title'   => 'Login Kembali',
                'message' => 'Sesi Anda telah berakhir karena tidak ada aktivitas beberapa saat. Silakan reload halaman & login kembali.',
            ], 419);
        });

        $exceptions->render(function (\Spatie\Permission\Exceptions\UnauthorizedException $e, $request) {
            return response()->json([
                'message' => 'Kamu tidak memiliki izin untuk mengakses fitur ini. Aktifkan pada menu Level User dan atur hak aksesnya atau hubungi admin',
            ], 403);
        });

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
