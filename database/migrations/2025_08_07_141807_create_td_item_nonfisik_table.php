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
        Schema::create('td_item_nonfisik', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('nama', 150);
            $table->foreignId('item_nonfisik_tipe_id')->nullable()
                ->constrained('td_item_nonfisik_tipe')->onDelete('set null')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('created_by')->nullable()
                ->constrained('users')->onDelete('set null')->nullable();
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
        Schema::dropIfExists('td_item_nonfisik');
    }
};
