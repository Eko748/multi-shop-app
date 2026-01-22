<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('toko_group_item', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('toko_group_id');
            $table->unsignedBigInteger('toko_id');
            $table->timestamps();

            $table->unique(['toko_group_id', 'toko_id']);

            $table->foreign('toko_group_id')
                ->references('id')
                ->on('toko_group')
                ->cascadeOnDelete();

            $table->foreign('toko_id')
                ->references('id')
                ->on('toko')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('toko_group_item');
    }
};
