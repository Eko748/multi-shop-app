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
        Schema::create('stock_barang_bermasalah', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_barang_batch_id');
            $table->enum('status', ['hilang', 'mati', 'rusak']);
            $table->integer('qty');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_barang_bermasalah');
    }
};
