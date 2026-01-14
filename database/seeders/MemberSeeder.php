<?php

namespace Database\Seeders;

use App\Models\Member;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class MemberSeeder extends Seeder
{
    public function run(): void
    {
        $jsonPath = storage_path('app/private/json/member.json');

        if (!File::exists($jsonPath)) {
            $this->command->error("File member.json tidak ditemukan di: $jsonPath");
            return;
        }

        $json = File::get($jsonPath);
        $data = json_decode($json, true);

        if (!isset($data['member'])) {
            $this->command->error("Data 'member' tidak ditemukan dalam file JSON.");
            return;
        }

        foreach ($data['member'] as $item) {
            Member::updateOrCreate(
                ['id' => $item['id']],
                [
                    'toko_id'      => $item['id_toko'],
                    'level_info'   => $item['level_info'],
                    'nama'  => $item['nama_member'],
                    'no_hp'        => $item['no_hp'],
                    'alamat'       => $item['alamat'],
                    'created_at'   => $item['created_at'],
                    'updated_at'   => $item['updated_at'],
                    'deleted_at'   => $item['deleted_at'],
                ]
            );
        }

        $this->command->info("Seeder Member berhasil dijalankan.");
    }
}
