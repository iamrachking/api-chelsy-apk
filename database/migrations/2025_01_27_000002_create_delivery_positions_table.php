<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('set null');
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->decimal('accuracy', 8, 2)->nullable(); // Précision en mètres
            $table->decimal('speed', 8, 2)->nullable(); // Vitesse en km/h
            $table->decimal('heading', 5, 2)->nullable(); // Direction en degrés (0-360)
            $table->timestamp('recorded_at');
            $table->timestamps();

            // Index pour les requêtes fréquentes
            $table->index(['driver_id', 'recorded_at']);
            $table->index(['order_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_positions');
    }
};

