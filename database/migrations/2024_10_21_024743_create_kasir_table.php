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
        Schema::create('transaksi_kasir', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->unsignedBigInteger('toko_id');
            $table->string('nota')->unique();
            $table->dateTime('tanggal');
            $table->integer('total_qty');
            $table->decimal('total_nominal', 15, 2);
            $table->decimal('total_bayar', 15, 2);
            $table->decimal('total_diskon', 15, 2)->nullable();
            $table->enum('metode', ['cash', 'cashless'])->default('cash');
            $table->unsignedBigInteger('member_id')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaksi_kasir');
    }
};
