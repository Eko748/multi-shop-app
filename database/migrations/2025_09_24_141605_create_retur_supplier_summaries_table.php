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
        Schema::create('retur_supplier_summary', function (Blueprint $table) {
            $table->id();
            $table->decimal('sub_total_refund', 15, 2)->default(0);
            $table->decimal('sub_total_hpp', 15, 2)->default(0);
            $table->decimal('sub_total_selisih', 15, 2)->default(0);
            $table->enum('status', ['seimbang', 'rugi', 'untung'])->default('seimbang');

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
        Schema::dropIfExists('retur_supplier_summary');
    }
};
