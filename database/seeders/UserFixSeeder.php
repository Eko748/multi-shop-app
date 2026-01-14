<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class UserFixSeeder extends Seeder
{
    public function run(): void
    {
        $jsonPath = storage_path('app/private/json/user.json');

        if (!File::exists($jsonPath)) {
            $this->command->error("File user.json tidak ditemukan di: $jsonPath");
            return;
        }

        $json = File::get($jsonPath);
        $data = json_decode($json, true);

        if (!isset($data['users'])) {
            $this->command->error("Data 'users' tidak ditemukan dalam file JSON.");
            return;
        }

        foreach ($data['users'] as $item) {
            User::updateOrCreate(
                ['id' => $item['id']],
                [
                    'toko_id'       => $item['toko_id'],
                    'role_id'       => $item['role_id'],
                    'nama'          => $item['nama'],
                    'username'      => $item['username'],
                    'password'      => $item['password'], // diasumsikan sudah hashed
                    'alamat'        => $item['alamat'],
                    'ip_login'      => $item['ip_login'],
                    'last_activity' => $item['last_activity'],
                    'deleted_at'    => $item['deleted_at'],
                ]
            );
        }

        $this->command->info("Seeder User berhasil dijalankan.");
    }
}
