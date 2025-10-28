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
        Schema::create('bookings', function (Blueprint $table) {
             $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('table_id')->constrained('tables')->cascadeOnDelete();
            // no customer_id -> use name & phone
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->enum('status', ['pending', 'confirmed', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->decimal('price_per_hour', 10, 2)->default(0); // snapshot
            $table->decimal('total_hours', 8, 2)->default(0);
            $table->decimal('total_price', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['table_id','start_time','end_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
