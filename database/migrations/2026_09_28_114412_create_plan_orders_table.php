<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_order', function (Blueprint $table) {
            $table->id();
            // Relasi ke tabel toko (tanpa s)
            $table->foreignId('toko_id')->nullable()->constrained('toko')->nullOnDelete();
            $table->string('kode_plan')->unique();
            $table->decimal('grand_total', 15, 6)->default(0);
            $table->json('items'); // Menampung array: barang_id, nama, qty, hpp, total_hpp
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_order');
    }
};
