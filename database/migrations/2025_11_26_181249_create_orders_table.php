<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('restaurant_id')->constrained()->onDelete('cascade');
            $table->foreignId('address_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('driver_id')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('type', ['delivery', 'pickup'])->default('delivery');
            $table->enum('status', [
                'pending',
                'confirmed',
                'preparing',
                'ready',
                'out_for_delivery',
                'delivered',
                'picked_up',
                'cancelled'
            ])->default('pending');
            $table->decimal('subtotal', 10, 2);
            $table->decimal('delivery_fee', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->foreignId('promo_code_id')->nullable()->constrained('promo_codes')->onDelete('set null');
            $table->datetime('scheduled_at')->nullable(); // Heure de livraison/récupération souhaitée
            $table->datetime('delivered_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->text('special_instructions')->nullable();
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('dish_id')->constrained()->onDelete('cascade');
            $table->string('dish_name'); // Snapshot au moment de la commande
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_price', 10, 2);
            $table->json('selected_options')->nullable();
            $table->text('special_instructions')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
