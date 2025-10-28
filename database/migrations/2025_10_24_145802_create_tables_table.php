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
        Schema::create('tables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->string('name'); // Table 1, PS Room 3...
            $table->string('type')->nullable(); // cafe_table, ps_room, vip...
            $table->unsignedSmallInteger('capacity')->default(1);
            $table->decimal('price_per_hour', 10, 2)->nullable();
            $table->decimal('min_hours', 5, 2)->default(1);
            $table->enum('status',['free','reserved','busy','maintenance'])->default('free');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['branch_id','name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tables');
    }
};
