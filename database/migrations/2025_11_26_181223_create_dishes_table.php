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
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description');
            $table->decimal('price', 10, 2);
            $table->decimal('discount_price', 10, 2)->nullable();
            $table->json('images'); // ["image1.jpg", "image2.jpg"]
            $table->integer('preparation_time_minutes')->default(30);
            $table->json('allergens')->nullable(); // ["gluten", "lactose"]
            $table->json('nutritional_info')->nullable(); // {"calories": 500, "protein": 20, ...}
            $table->boolean('is_available')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_new')->default(false);
            $table->boolean('is_vegetarian')->default(false);
            $table->boolean('is_specialty')->default(false);
            $table->integer('order_count')->default(0); // Pour trier par popularité
            $table->decimal('average_rating', 3, 2)->default(0);
            $table->integer('review_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dishes');
    }
};
