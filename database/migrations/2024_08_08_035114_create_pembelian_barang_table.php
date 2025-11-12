<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pembelian_barang', function (Blueprint $table) {
            $table->id('id');
            $table->string('id_supplier')->nullable();
            $table->string('id_users')->nullable();
            $table->string('no_nota')->nullable();
            $table->dateTime('tgl_nota')->nullable();
            $table->integer('total_item')->nullable();
            $table->double('total_nilai')->nullable();
            $table->char('label')->nullable();
            $table->enum('status', ['progress', 'success', 'failed', 'mixed'])->default('progress');
            $table->enum('tipe', ['cash', 'hutang'])->default('cash');
            $table->softDeletes();
        });
    }
};
