<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('toko_id');
            $table->unsignedBigInteger('role_id');
            $table->string('nama');
            $table->string('username');
            $table->string('password');
            $table->text('alamat')->default('Cirebon');
            $table->string('ip_login')->nullable();
            $table->timestamp('last_activity')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
};
