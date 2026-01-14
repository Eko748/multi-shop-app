<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('kas_transaksi', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('kas_id');
            $table->enum('tipe', ['in', 'out']);
            $table->string('kode_transaksi')->unique();
            $table->decimal('total_nominal', 15, 2);
            $table->string('kategori')->nullable();
            $table->text('keterangan')->nullable();
            $table->enum('item', ['kecil', 'besar', 'hutang', 'piutang']);
            $table->morphs('sumber');
            $table->dateTime('tanggal')->useCurrent();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kas_transaksi');
    }
};
