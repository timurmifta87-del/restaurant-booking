<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hall_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('number');
            $table->unsignedInteger('seats');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['hall_id', 'number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tables');
    }
};