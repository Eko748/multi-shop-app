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
        Schema::create('stock_barang_bulanan', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('toko_id');
            $table->foreign('toko_id')
                ->references('id')
                ->on('toko')
                ->onDelete('restrict');

            $table->unsignedBigInteger('jenis_barang_id');
            $table->foreign('jenis_barang_id')
                ->references('id')
                ->on('jenis_barang')
                ->onDelete('restrict');

            $table->year('tahun');
            $table->tinyInteger('bulan');

            $table->integer('qty_awal')->default(0);
            $table->integer('qty_masuk')->default(0);
            $table->integer('qty_keluar')->default(0);
            $table->integer('qty_sisa')->default(0);
            $table->decimal('nilai_aset', 18, 6)->default(0);
            $table->integer('qty_retur')->default(0);
            $table->decimal('nilai_retur', 18, 6)->default(0);

            $table->timestamps();

            $table->unique(['toko_id', 'jenis_barang_id', 'tahun', 'bulan'], 'stock_bulanan_toko_jenis_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_barang_bulanan');
    }
};
