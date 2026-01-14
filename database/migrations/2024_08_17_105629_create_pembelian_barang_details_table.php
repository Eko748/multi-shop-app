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
        Schema::create('pembelian_barang_detail', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pembelian_barang_id');
            $table->unsignedBigInteger('barang_id');
            $table->unsignedBigInteger('stock_barang_batch_id');
            $table->integer('qty');
            $table->decimal('harga_beli', 15, 2);
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pembelian_barang_detail');
    }
};
