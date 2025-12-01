<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Recrée le fichier Service Account Firebase en production si nécessaire
        $json = env('FIREBASE_CREDENTIALS_JSON');

        if ($json) {
            $path = storage_path('app/firebase-credentials.json');

            // Ne recrée le fichier que s'il n'existe pas déjà
            if (!file_exists($path)) {
                file_put_contents($path, $json);
            }
        }
    }
}