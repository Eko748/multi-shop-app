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
        Schema::create('stock_barang', function (Blueprint $table) {
            $table->id('id');
            $table->unsignedBigInteger('toko_id');
            $table->unsignedBigInteger('barang_id');
            $table->integer('stok')->default(0);
            $table->decimal('hpp_awal', 15, 2)->nullable();
            $table->decimal('hpp_baru', 15, 2)->nullable();
            $table->string('level_harga')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['toko_id', 'barang_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_barang');
    }
};
