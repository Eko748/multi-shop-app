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
        Schema::create('td_penjualan_nonfisik_detail', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('penjualan_nonfisik_id')->nullable()
                ->constrained('td_penjualan_nonfisik')->onDelete('set null');
            $table->foreignId('item_nonfisik_id')->nullable()
                ->constrained('td_item_nonfisik')->onDelete('set null');
            $table->decimal('hpp', 15, 2);
            $table->decimal('harga_jual', 15, 2);
            $table->unsignedInteger('qty')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('td_penjualan_nonfisik_detail');
    }
};
