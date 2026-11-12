<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_dish', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dish_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('price', 10, 2); // цена блюда на момент заказа
            $table->timestamps();

            $table->unique(['booking_id', 'dish_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_dish');
    }
};
