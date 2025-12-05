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
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable(); // Titre optionnel de la bannière
            $table->string('image'); // Image de la bannière (obligatoire)
            $table->string('link')->nullable(); // Lien optionnel (vers un plat, catégorie, etc.)
            $table->integer('order')->default(0); // Ordre d'affichage
            $table->boolean('is_active')->default(true); // Actif/Inactif
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};
