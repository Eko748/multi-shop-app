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
        Schema::create('td_item_nonfisik_tipe', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->unsignedBigInteger('toko_id');
            $table->string('nama', 25);
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('created_by')->nullable()
                ->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()
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
        Schema::dropIfExists('td_item_nonfisik_tipe');
    }
};
