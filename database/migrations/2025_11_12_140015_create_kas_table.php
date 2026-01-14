<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('kas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('toko_id');
            $table->enum('tipe_kas', ['besar', 'kecil']);
            $table->unsignedBigInteger('jenis_barang_id')->nullable();
            $table->decimal('saldo_awal', 15, 2)->default(0);
            $table->decimal('saldo', 15, 2)->default(0);
            $table->dateTime('tanggal');
            $table->timestamps();
            $table->index(['toko_id', 'jenis_barang_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kas');
    }
};
