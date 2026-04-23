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
        Schema::create('pembelian_barang_detail_adjustment', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('toko_id');

            $table->foreignId('pembelian_barang_id');
            $table->foreignId('pembelian_barang_detail_id');
            $table->foreignId('stock_barang_batch_id');

            $table->integer('old_qty')->default(0);
            $table->integer('new_qty')->default(0);

            $table->decimal('old_harga', 15, 6)->default(0);
            $table->decimal('new_harga', 15, 6)->default(0);

            $table->decimal('selisih_harga', 15, 6)->default(0);

            $table->decimal('nominal_laba_rugi', 15, 6)->default(0);
            $table->decimal('nominal_stok', 15, 6)->default(0);

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();

            $table->index('pembelian_barang_id', 'pbda_pembelian_idx');
            $table->index('pembelian_barang_detail_id', 'pbda_detail_idx');
            $table->index('stock_barang_batch_id', 'pbda_batch_idx');
            $table->index(['created_at'], 'pbda_created_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pembelian_barang_detail_adjustment');
    }
};
