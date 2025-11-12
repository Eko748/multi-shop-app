<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class AddPermissionMiddlewareToRoutes extends Command
{
    protected $signature = 'route:apply-permissions';
    protected $description = 'Tambahkan middleware permission ke routes/web.php sesuai method dan URI';

    public function handle(): void
    {
        $path = base_path('routes/web.php');
        if (!file_exists($path)) {
            $this->error("File routes/web.php tidak ditemukan.");
            return;
        }

        $lines = file($path);
        $updatedLines = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);

            // Deteksi baris yang merupakan definisi route dengan controller
            if (preg_match('/Route::(get|post|put|delete|patch)\s*\(\s*[\'"]([^\'"]+)[\'"]\s*,\s*\[(.*?)::class\s*,\s*[\'"](\w+)[\'"]\]/', $line, $matches)) {
                $method = strtoupper($matches[1]);
                $uri = $matches[2];
                $controller = $matches[3];
                $action = $matches[4];

                $permission = $method . ' ' . $uri;

                // Skip jika sudah mengandung middleware permission
                if (str_contains($line, '->middleware(')) {
                    $updatedLines[] = $line;
                    continue;
                }

                // Tambahkan middleware di sebelum penutup ');'
                $line = rtrim($line);
                if (str_ends_with($line, ');')) {
                    $line = substr($line, 0, -2); // buang );
                    $line .= "->middleware('permission:\$permission\');\n";
                }

                $updatedLines[] = $line;
            } else {
                $updatedLines[] = $line;
            }
        }

        // Backup file lama
        File::copy($path, $path . '.bak');

        // Tulis ulang file dengan update
        file_put_contents($path, implode('', $updatedLines));

        $this->info("✅ Middleware berhasil ditambahkan ke routes/web.php");
        $this->info("📦 Backup tersimpan di routes/web.php.bak");
    }
}
