<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kas_mutasi', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('kas_asal_id');
            $table->unsignedBigInteger('kas_tujuan_id');
            $table->decimal('nominal', 18, 2);
            $table->string('keterangan')->nullable();
            $table->dateTime('tanggal')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kas_mutasi');
    }
};
