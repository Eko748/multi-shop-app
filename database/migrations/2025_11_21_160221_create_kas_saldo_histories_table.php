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
        Schema::create('kas_saldo_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('kas_id');
            $table->integer('tahun');
            $table->integer('bulan');
            $table->decimal('saldo_awal', 18, 2)->default(0);
            $table->decimal('saldo_akhir', 18, 2)->default(0);
            $table->timestamps();

            $table->unique(['kas_id', 'tahun', 'bulan']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kas_saldo_history');
    }
};
