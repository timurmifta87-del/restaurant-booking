<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dishes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_category_id')->constrained()->restrictOnDelete();
            $table->string('name')->index();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->string('image_path')->nullable();
            $table->boolean('is_available')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dishes');
    }
};