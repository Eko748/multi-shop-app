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
        Schema::create('retur_member', function (Blueprint $table) {
            $table->id();
            $table->foreignId('toko_id')->nullable()->constrained('toko')->nullOnDelete();
            $table->foreignId('member_id')->nullable()->constrained('member')->nullOnDelete();
            $table->enum('status', [
                'draft',
                'proses',
                'selesai'
            ])->default('draft');
            $table->enum('tipe', [
                'dari_member',
                'dari_cabang',
                'di_toko_utama',
                'ke_supplier',
            ])->default('dari_member');

            $table->dateTime('tanggal');
            $table->timestamps();
            $table->softDeletes();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('retur_member');
    }
};
