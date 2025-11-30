<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class DatabaseBootstrapper
{
    public static function migrateAndSeed()
    {
        // Vérifie si ça a déjà été exécuté
        if (!Cache::get('db_bootstrap_done', false)) {

            // Lancer les migrations
            Artisan::call('migrate', ['--force' => true]);

            // Lancer les seeders
            Artisan::call('db:seed', ['--force' => true]);

            // Marque comme exécuté
            Cache::forever('db_bootstrap_done', true);
        }
    }
}
