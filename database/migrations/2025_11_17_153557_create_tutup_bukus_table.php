<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tutup_buku', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('toko_id');
            $table->date('periode'); // misal 2025-01-31 (akhir bulan)
            $table->json('data_kas');
            // contoh json:
            // [
            //   { "kas_id": 1, "saldo_awal": 500000, "saldo_akhir": 750000 },
            //   { "kas_id": 2, "saldo_awal": 200000, "saldo_akhir": 180000 }
            // ]
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tutup_buku');
    }
};
