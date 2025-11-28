<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('dish_id')->nullable()->constrained()->onDelete('cascade');
            $table->enum('type', ['restaurant', 'dish', 'delivery'])->default('restaurant');
            $table->integer('rating'); // 1-5
            $table->text('comment')->nullable();
            $table->json('images')->nullable(); // Photos du plat
            $table->text('restaurant_response')->nullable();
            $table->datetime('restaurant_response_at')->nullable();
            $table->boolean('is_approved')->default(false); // Modération
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
