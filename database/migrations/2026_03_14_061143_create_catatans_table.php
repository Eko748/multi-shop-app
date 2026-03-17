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
        Schema::create('catatan', function (Blueprint $table) {
            $table->id();
            $table->text('keterangan');
            $table->boolean('is_read')->default(false);
            $table->foreignId('toko_asal_id')->nullable()
                ->constrained('toko')->onDelete('set null')->nullable();
            $table->foreignId('toko_tujuan_id')->nullable()
                ->constrained('toko')->onDelete('set null')->nullable();
            $table->foreignId('created_by')->nullable()
                ->constrained('users')->onDelete('set null')->nullable();
            $table->foreignId('read_by')->nullable()
                ->constrained('users')->onDelete('set null')->nullable();
            $table->foreignId('updated_by')->nullable()
                ->constrained('users')->onDelete('set null')->nullable();
            $table->foreignId('deleted_by')->nullable()
                ->constrained('users')->onDelete('set null')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catatan');
    }
};
