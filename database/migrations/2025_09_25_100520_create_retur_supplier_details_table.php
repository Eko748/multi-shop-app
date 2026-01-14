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
        Schema::create('retur_supplier_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('retur_supplier_id')->nullable()->constrained('retur_supplier')->nullOnDelete();
            $table->foreignId('barang_id')->nullable()->constrained('barang')->nullOnDelete();
            $table->foreignId('retur_member_detail_id')->nullable()->constrained('retur_member_detail')->nullOnDelete();
            $table->foreignId('pembelian_barang_detail_id')->nullable()->constrained('pembelian_barang_detail')->nullOnDelete();
            $table->enum('tipe_kompensasi', ['refund', 'barang', 'kombinasi'])->nullable();
            $table->integer('qty')->default(0);
            $table->integer('qty_barang')->default(0);
            $table->integer('qty_refund')->default(0);
            $table->decimal('hpp', 15, 2)->nullable();
            $table->decimal('total_hpp_barang', 15, 2)->default(0);
            $table->decimal('jumlah_refund', 15, 2)->default(0);
            $table->decimal('total_refund_real', 15, 2)->default(0);
            $table->decimal('total_refund', 15, 2)->default(0);
            $table->decimal('total_hpp', 15, 2)->default(0);
            $table->decimal('selisih', 15, 2)->default(0);
            $table->enum('keterangan', ['seimbang', 'rugi', 'untung'])->nullable();
            $table->decimal('harga_jual', 15, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('retur_supplier_detail');
    }
};
