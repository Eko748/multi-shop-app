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
        Schema::create('piutang', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('kas_id');
            $table->unsignedBigInteger('toko_id');
            $table->unsignedBigInteger('piutang_tipe_id');
            $table->decimal('nominal', 15, 2)->nullable();
            $table->decimal('sisa', 15, 2)->nullable();
            $table->datetime('tanggal');
            $table->string('keterangan');
            $table->boolean('status')->default(false);
            $table->enum('jangka', (['pendek', 'panjang']))->nullable();
            $table->nullableMorphs('sumber');
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
        Schema::dropIfExists('piutang');
    }
};
