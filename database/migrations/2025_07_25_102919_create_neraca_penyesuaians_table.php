<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('neraca_penyesuaian', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('toko_id');
            $table->decimal('nominal', 15, 6);
            $table->dateTime('tanggal');
            $table->text('pesan');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('neraca_penyesuaian');
    }
};
