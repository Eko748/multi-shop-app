<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_opnames', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('toko_id')->constrained('toko')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('tanggal');
            $table->enum('status', ['draft', 'selesai', 'dibatalkan'])->default('draft');
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_opname_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('stock_opname_id')->constrained('stock_opnames')->cascadeOnDelete();
            $table->foreignId('stock_barang_batch_id')->constrained('stock_barang_batch')->cascadeOnDelete();
            $table->integer('qty_sistem');
            $table->integer('qty_fisik');
            $table->integer('selisih');
            $table->enum('status_kondisi', ['normal', 'mati', 'rusak', 'hilang'])->default('normal');
            $table->text('catatan_item')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_opname_details');
        Schema::dropIfExists('stock_opnames');
    }
};
