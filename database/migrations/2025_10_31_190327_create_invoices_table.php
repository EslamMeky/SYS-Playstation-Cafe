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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
           $table->foreignId('session_id')->nullable()->constrained()->onDelete('cascade');

            // حسابات الفاتورة
            $table->decimal('session_total', 10, 2)->default(0); // قيمة الجلسة
            $table->decimal('items_total', 10, 2)->default(0); // قيمة الطلبات
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('final_total', 10, 2)->default(0);
            $table->enum('payment_method', ['cash','visa','wallet'])->default('cash');
            $table->enum('status',['pending','paid'])->default('pending'); // حالة الدفع
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
