<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;


class ScanRoleAccess extends Command
{
    protected $signature = 'scan:role-access';
    protected $description = 'Scan controllers for role-based access checks using id_level';

    public function handle()
    {
        $controllerPath = app_path('Http/Controllers');
        $files = File::allFiles($controllerPath);

        $results = [];

        foreach ($files as $file) {
            $path = $file->getRealPath();
            $content = file_get_contents($path);

            // Bangun nama class lengkap
            $className = 'App\\Http\\Controllers\\' . str_replace(
                ['/', '.php'],
                ['\\', ''],
                str_replace($controllerPath . DIRECTORY_SEPARATOR, '', $file->getRelativePathname())
            );

            // Tangkap semua public function (tanpa harus cocok { } secara utuh)
            preg_match_all('/public function (\w+)\s*\((.*?)\)\s*\{([\s\S]*?)\n\s*\}/m', $content, $matches, PREG_SET_ORDER);

            foreach ($matches as $match) {
                $method = $match[1];
                $body = $match[3]; // isi dari function

                // Cari pola in_array(Auth::user()->id_level, [...])
                if (preg_match('/in_array\s*\(\s*Auth::user\(\)->id_level\s*,\s*\[\s*([0-9,\s]+)\s*\]/', $body, $roleMatch)) {
                    $roles = array_map('trim', explode(',', $roleMatch[1]));

                    $results[] = [
                        'controller' => $className,
                        'method' => $method,
                        'roles' => implode(', ', $roles),
                    ];
                }
            }
        }

        // Tampilkan hasil
        $this->info(str_pad('CONTROLLER@METHOD', 50) . 'ROLES');

        foreach ($results as $row) {
            $this->line(str_pad($row['controller'] . '@' . $row['method'], 50) . $row['roles']);
        }

        // Tampilkan hasil
        $this->info(str_pad('CONTROLLER@METHOD', 50) . 'ROLES');
        foreach ($results as $row) {
            $this->line(str_pad($row['controller'] . '@' . $row['method'], 50) . $row['roles']);
        }

        // Export ke Excel
        Excel::store(new class(collect($results)) implements FromCollection, WithHeadings {
            protected $data;

            public function __construct(Collection $data)
            {
                $this->data = $data;
            }

            public function collection()
            {
                return $this->data->map(function ($item) {
                    return [
                        'controller' => $item['controller'],
                        'method' => $item['method'],
                        'roles' => $item['roles'],
                    ];
                });
            }

            public function headings(): array
            {
                return ['Controller', 'Method', 'Roles'];
            }
        }, 'role_access.xlsx');

        $this->info('📁 Hasil diekspor ke storage/app/role_access.xlsx');

        return Command::SUCCESS;
    }
}
