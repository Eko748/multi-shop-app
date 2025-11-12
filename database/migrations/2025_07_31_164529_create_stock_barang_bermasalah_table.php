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
            $table->unsignedBigInteger('stock_barang_id')->nullable()->index();
            $table->string('status');
            $table->integer('qty');
            $table->decimal('hpp', 15, 2);
            $table->decimal('total_hpp', 15, 2);
            $table->timestamps();
            $table->foreign('stock_barang_id')->references('id')->on('stock_barang')->onDelete('set null');
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
