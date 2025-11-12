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
        Schema::create('retur_supplier', function (Blueprint $table) {
            $table->id();
            $table->foreignId('toko_id')->nullable()->constrained('toko')->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('supplier')->nullOnDelete();
            $table->enum('status', ['proses', 'selesai'])->default('proses');
            $table->enum('tipe_retur', ['member', 'pembelian']);
            $table->dateTime('tanggal');
            $table->integer('qty')->default(0);
            $table->decimal('total_refund', 15, 2)->default(0);
            $table->decimal('total_hpp', 15, 2)->default(0);
            $table->decimal('total_selisih', 15, 2)->default(0);
            $table->enum('keterangan', ['seimbang', 'rugi', 'untung'])->nullable();
            $table->dateTime('verify_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('retur_supplier');
    }
};
