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
        // tabel retur_member_detail
        Schema::create('retur_member_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('retur_id')->nullable()->constrained('retur_member')->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('supplier')->nullOnDelete();
            $table->foreignId('barang_id')->nullable()->constrained('barang')->nullOnDelete();
            $table->foreignId('detail_kasir_id')->nullable()->constrained('detail_kasir')->nullOnDelete();
            $table->enum('tipe_kompensasi', ['refund', 'barang'])->nullable();
            $table->integer('qty_request');
            $table->integer('qty_barang')->default(0);
            $table->integer('qty_refund')->default(0);
            $table->decimal('hpp', 15, 2)->nullable();
            $table->decimal('total_hpp', 15, 2)->nullable();
            $table->decimal('total_hpp_barang', 15, 2)->nullable();
            $table->decimal('jumlah_refund', 15, 2)->nullable();
            $table->decimal('total_refund', 15, 2)->nullable();
            $table->decimal('harga_jual', 15, 2)->nullable();
            $table->integer('qty_ke_supplier')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('retur_member_detail');
    }
};
