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
        Schema::create('pengeluaran', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('kas_id');
            $table->unsignedBigInteger('toko_id');
            $table->unsignedBigInteger('pengeluaran_tipe_id')->nullable();
            $table->decimal('nominal', 15, 2)->nullable();
            $table->datetime('tanggal');
            $table->string('keterangan')->nullable();
            $table->enum('aset', ['kecil', 'besar'])->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengeluaran');
    }
};
