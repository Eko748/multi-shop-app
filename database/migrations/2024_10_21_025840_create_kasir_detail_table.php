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
        Schema::create('transaksi_kasir_detail', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transaksi_kasir_id');
            $table->unsignedBigInteger('stock_barang_batch_id');
            $table->string('qrcode')->unique()->nullable();
            $table->integer('qty');
            $table->decimal('nominal', 15, 2);
            $table->decimal('diskon', 15, 2)->nullable();
            $table->integer('retur_qty')->nullable();
            $table->unsignedBigInteger('retur_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaksi_kasir_detail');
    }
};
