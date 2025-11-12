<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier', function (Blueprint $table) {
            $table->id('id');
            $table->string('nama_supplier');
            $table->string('email')->nullable();
            $table->text('alamat')->nullable();
            $table->string('contact')->nullable();
            $table->softDeletes();
        });
    }

};
