<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dish_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dish_id')->constrained()->onDelete('cascade');
            $table->string('name'); // Ex: "Taille", "Cuisson", "Ingrédients"
            $table->string('type')->default('select'); // select, checkbox, radio
            $table->boolean('is_required')->default(false);
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        Schema::create('dish_option_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dish_option_id')->constrained()->onDelete('cascade');
            $table->string('value'); // Ex: "Petite", "Moyenne", "Grande"
            $table->decimal('price_modifier', 8, 2)->default(0); // +5.00 ou -2.00
            $table->integer('order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dish_option_values');
        Schema::dropIfExists('dish_options');
    }
};
