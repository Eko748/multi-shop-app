<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Toko;
use App\Models\TokoGroup;
use App\Models\TokoGroupItem;

class TokoGroupSeeder extends Seeder
{
    public function run(): void
    {
        // Ambil parent toko yang punya anak
        $parentTokoIds = Toko::whereNotNull('parent_id')
            ->distinct()
            ->pluck('parent_id');

        foreach ($parentTokoIds as $parentId) {

            // Ambil parent toko
            $parentToko = Toko::find($parentId);
            if (!$parentToko) {
                continue;
            }

            // Tentukan kode nama (singkatan > nama)
            $kode = $parentToko->singkatan
                ? strtoupper($parentToko->singkatan)
                : strtoupper(preg_replace('/\s+/', '', $parentToko->nama));

            // 1️⃣ Buat / ambil group
            $group = TokoGroup::firstOrCreate(
                ['parent_toko_id' => $parentId],
                [
                    'nama' => 'Group Toko ' . $kode . ' 001',
                    'keterangan' => 'Auto generated group',
                ]
            );

            // 2️⃣ Masukkan PARENT TOKO ke group
            TokoGroupItem::updateOrInsert(
                [
                    'toko_group_id' => $group->id,
                    'toko_id'       => $parentId,
                ],
                [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            // 3️⃣ Masukkan SEMUA CHILD TOKO ke group
            $childTokos = Toko::where('parent_id', $parentId)->pluck('id');

            foreach ($childTokos as $tokoId) {
                TokoGroupItem::updateOrInsert(
                    [
                        'toko_group_id' => $group->id,
                        'toko_id'       => $tokoId,
                    ],
                    [
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }

        $this->command->info('Seeder TokoGroup & TokoGroupItem (termasuk parent) berhasil dijalankan.');
    }
}
