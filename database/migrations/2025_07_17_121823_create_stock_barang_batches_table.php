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
        Schema::create('stock_barang_batch', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_barang_id');
            $table->string('qrcode')->unique();
            $table->integer('qty_masuk');
            $table->integer('qty_sisa');
            $table->decimal('harga_beli', 15, 2);
            $table->decimal('hpp_awal', 15, 2);
            $table->decimal('hpp_baru', 15, 2);
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_barang_batch');
    }
};
