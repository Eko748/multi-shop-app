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
        Schema::create('td_penjualan_nonfisik', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('dompet_kategori_id')->nullable()
                ->constrained('td_dompet_kategori')->onDelete('set null');
            $table->string('nota', 50)->unique();
            $table->decimal('total_bayar', 15, 2);
            $table->decimal('total_hpp', 15, 2);
            $table->decimal('total_harga_jual', 15, 2);
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('created_by')->nullable()
                ->constrained('users')->onDelete('set null')->nullable();
            $table->foreignId('deleted_by')->nullable()
                ->constrained('users')->onDelete('set null')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('td_penjualan_nonfisik');
    }
};
