<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('pengiriman_barang', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('toko_asal_id');
            $table->unsignedBigInteger('toko_tujuan_id');
            $table->enum('status', ['pending', 'progress', 'success', 'canceled'])->default('pending');
            $table->string('no_resi');
            $table->string('ekspedisi');
            $table->unsignedBigInteger('send_by');
            $table->timestamp('send_at');
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

};
