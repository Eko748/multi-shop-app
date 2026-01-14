<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pembelian_barang', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('toko_id');
            $table->unsignedBigInteger('supplier_id');
            $table->enum('status', ['progress', 'success', 'completed_debt', 'success_debt'])->default('progress');
            $table->string('nota')->nullable();
            $table->dateTime('tanggal')->nullable();
            $table->integer('qty')->nullable()->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->enum('tipe_kas', ['besar', 'kecil'])->default('kecil');
            $table->unsignedBigInteger('jenis_barang_id');
            $table->enum('tipe', ['cash', 'hutang'])->default('cash');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
};
