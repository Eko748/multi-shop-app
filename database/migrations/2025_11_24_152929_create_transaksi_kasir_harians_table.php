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
        Schema::create('transaksi_kasir_harian', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('toko_id');
            $table->unsignedBigInteger('jenis_barang_id');
            $table->date('tanggal');
            $table->integer('total_transaksi')->default(0);
            $table->integer('total_qty')->default(0);
            $table->decimal('total_nominal', 15, 2)->default(0);
            $table->decimal('total_bayar', 15, 2)->default(0);
            $table->decimal('total_diskon', 15, 2)->default(0);
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['toko_id', 'tanggal']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaksi_kasir_harian');
    }
};
