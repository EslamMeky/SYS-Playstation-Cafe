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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->unsignedBigInteger('session_id')->nullable(); // لو رغبت تربط بجلسة
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->enum('type', ['dine_in','session','takeaway'])->default('takeaway');
            $table->decimal('total_price', 10, 2)->default(0);
            $table->enum('status', ['pending','paid','cancelled'])->default('pending');
            $table->string('payment_method')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
