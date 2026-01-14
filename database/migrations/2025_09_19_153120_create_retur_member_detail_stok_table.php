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
        Schema::create('retur_member_detail_batch', function (Blueprint $table) {
            $table->id();
            $table->foreignId('retur_member_detail_id')
                ->constrained('retur_member_detail')
                ->cascadeOnDelete();
            $table->foreignId('stock_barang_batch_id')
                ->constrained('stock_barang_batch')
                ->restrictOnDelete();
            $table->integer('qty')->default(0); // jumlah diambil dari stok_detail ini
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('retur_member_detail_batch');
    }
};
