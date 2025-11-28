<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('history')->nullable();
            $table->text('values')->nullable();
            $table->string('chef_name')->nullable();
            $table->text('team_description')->nullable();
            $table->string('phone');
            $table->string('email');
            $table->string('address');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->json('opening_hours'); // {"monday": {"open": "09:00", "close": "22:00"}, ...}
            $table->json('social_media')->nullable(); // {"facebook": "...", "instagram": "...", "twitter": "..."}
            $table->integer('delivery_radius_km')->default(5); // Rayon de livraison en km
            $table->decimal('delivery_fee_base', 8, 2)->default(0); // Frais de base
            $table->decimal('delivery_fee_per_km', 8, 2)->default(0); // Frais par km
            $table->decimal('minimum_order_amount', 8, 2)->default(0);
            $table->string('logo')->nullable();
            $table->json('images')->nullable(); // Galerie d'images
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurants');
    }
};
