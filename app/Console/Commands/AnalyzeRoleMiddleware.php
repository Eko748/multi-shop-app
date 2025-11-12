<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AnalyzeRoleMiddleware extends Command
{
    protected $signature = 'analyze:roles';
    protected $description = 'Analyze controller methods for id_level access and suggest role middleware';

    public function handle()
    {
        $controllerPath = app_path('Http/Controllers');
        $files = File::allFiles($controllerPath);

        $results = [];

        // Step 1: Scan all controller methods
        foreach ($files as $file) {
            $path = $file->getRealPath();
            $content = file_get_contents($path);

            // Build full class name
            $className = 'App\\Http\\Controllers\\' . str_replace(
                ['/', '.php'],
                ['\\', ''],
                str_replace($controllerPath . DIRECTORY_SEPARATOR, '', $file->getRelativePathname())
            );

            // Match all public methods
            preg_match_all('/public function (\w+)\s*\((.*?)\)\s*\{([\s\S]*?)\n\s*\}/m', $content, $matches, PREG_SET_ORDER);

            foreach ($matches as $match) {
                $method = $match[1];
                $body = $match[3];

                // Match id_level role check
                if (preg_match('/in_array\s*\(\s*Auth::user\(\)->id_level\s*,\s*\[\s*([0-9,\s]+)\s*\]/', $body, $roleMatch)) {
                    $roles = array_map('trim', explode(',', $roleMatch[1]));

                    $results[] = [
                        'controller' => $className,
                        'method' => $method,
                        'roles' => implode(',', $roles),
                    ];
                }
            }
        }

        // Step 2: Match to routes
        $routes = Route::getRoutes();
        $finalList = [];

        foreach ($routes as $route) {
            $action = $route->getActionName();

            if (!Str::contains($action, '@')) continue;

            [$controllerClass, $method] = explode('@', $action);

            $match = collect($results)->firstWhere(function ($item) use ($controllerClass, $method) {
                return $item['controller'] === $controllerClass && $item['method'] === $method;
            });

            if ($match) {
                $middlewares = $route->gatherMiddleware();
                $hasRoleMiddleware = collect($middlewares)->contains(function ($m) {
                    return Str::startsWith($m, 'role:') || Str::startsWith($m, 'permission:');
                });

                foreach ($route->methods() as $httpMethod) {
                    // Abaikan HEAD jika ada GET
                    if ($httpMethod === 'HEAD') continue;

                    $finalList[] = [
                        'method' => $httpMethod,
                        'uri' => $route->uri(),
                        'controller' => $controllerClass,
                        'method_name' => $method,
                        'roles' => $match['roles'],
                        'middleware_status' => $hasRoleMiddleware ? 'Sudah Ada' : 'Perlu Middleware',
                    ];
                }
            }
        }


        // Step 3: Output to terminal
        $this->info(str_pad('METHOD', 10) . str_pad('URI', 40) . str_pad('ROLES', 20) . 'STATUS');
        foreach ($finalList as $item) {
            $this->line(
                str_pad($item['method'], 10) .
                    str_pad($item['uri'], 40) .
                    str_pad($item['roles'], 20) .
                    $item['middleware_status']
            );
        }

        // Step 4: Suggest Middleware if Missing
        $this->line("\n📌 Rekomendasi Tambahan Middleware:");
        foreach ($finalList as $row) {
            if ($row['middleware_status'] === 'Perlu Middleware') {
                $this->line("Route::{$row['method']}('{$row['uri']}', [{$row['controller']}::class, '{$row['method_name']}'])"
                    . "->middleware('role:{$row['roles']}');");
            }
        }

        // Step 5: Export to Excel
        Excel::store(new class(collect($finalList)) implements FromCollection, WithHeadings {
            protected $data;
            public function __construct(Collection $data)
            {
                $this->data = $data;
            }

            public function collection()
            {
                return $this->data->map(fn($item) => [
                    'Method' => $item['method'],
                    'URI' => $item['uri'],
                    'Controller@Method' => $item['controller'] . '@' . $item['method_name'],
                    'Roles' => $item['roles'],
                    'Status' => $item['middleware_status'],
                ]);
            }

            public function headings(): array
            {
                return ['Method', 'URI', 'Controller@Method', 'Roles', 'Status'];
            }
        }, 'role_middleware_analysis.xlsx');

        $this->info('✅ File berhasil disimpan di: storage/app/role_middleware_analysis.xlsx');

        return Command::SUCCESS;
    }
}
