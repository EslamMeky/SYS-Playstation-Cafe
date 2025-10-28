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
        Schema::create('sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('table_id')->constrained()->onDelete('cascade');
            $table->string('customer_name');
            $table->dateTime('start_time');
            $table->dateTime('end_time')->nullable();
            $table->decimal('total_hours', 5, 2)->nullable();
            $table->decimal('price_per_hour', 8, 2);
            $table->decimal('total_amount', 10, 2)->nullable();
            $table->enum('status', ['ongoing', 'paused', 'ended'])->default('ongoing');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
    }
};
