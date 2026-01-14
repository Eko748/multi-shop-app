<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('laba_rugi', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('toko_id');
            $table->year('tahun');
            $table->tinyInteger('bulan');
            $table->decimal('pendapatan', 18, 2)->default(0);
            $table->decimal('beban', 18, 2)->default(0);
            $table->decimal('laba_bersih', 18, 2)->default(0);
            $table->timestamps();
            $table->unique(['toko_id', 'tahun', 'bulan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laba_rugi');
    }
};
