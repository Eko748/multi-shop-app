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

            $table->unsignedBigInteger('jenis_barang_id');

            $table->foreign('jenis_barang_id')
                ->references('id')
                ->on('jenis_barang')
                ->onDelete('restrict');

            $table->year('tahun');
            $table->tinyInteger('bulan');

            $table->integer('total_qty_awal')->default(0);
            $table->integer('total_qty_masuk')->default(0);
            $table->integer('total_qty_keluar')->default(0);
            $table->integer('total_qty_sisa')->default(0);
            $table->decimal('total_nilai_aset', 18, 2)->default(0);

            $table->timestamps();

            $table->unique(['jenis_barang_id', 'tahun', 'bulan'], 'stock_bulanan_jenis_unique');
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
