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
        Schema::create('session_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('session_id');
            $table->unsignedBigInteger('item_id');
            $table->integer('quantity')->default(1);
            $table->decimal('price', 10, 2); // السعر وقت الطلب
            $table->decimal('total', 10, 2)->virtualAs('quantity * price'); // السعر الإجمالي
            $table->timestamps();

            $table->foreign('session_id')->references('id')->on('sessions')->onDelete('cascade');
            $table->foreign('item_id')->references('id')->on('items')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('session_items');
    }
};
