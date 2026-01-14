<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('toko', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('nama');
            $table->string('singkatan')->nullable();
            $table->json('level_harga')->nullable();
            $table->string('wilayah')->default('Cirebon');
            $table->text('alamat')->default('Cirebon');
            $table->integer('pin')->nullable();
            $table->boolean('kas_detail')->default(true);
            $table->boolean('kasbon')->default(false);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
};
