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
        Schema::create('td_item_nonfisik_harga', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->unsignedBigInteger('toko_id');
            $table->foreignId('item_nonfisik_id')->nullable()
                ->constrained('td_item_nonfisik')->onDelete('set null');
            $table->foreignId('dompet_kategori_id')->nullable()
                ->constrained('td_dompet_kategori')->onDelete('set null');
            $table->decimal('hpp', 15, 2);
            $table->decimal('harga_jual', 15, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('td_item_nonfisik_harga');
    }
};
