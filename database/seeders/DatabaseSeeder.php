<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seeders dans l'ordre
        $this->call([
            RestaurantSeeder::class,
            CategorySeeder::class,
            DishSeeder::class,
            FAQSeeder::class,
        ]);

        // Créer un utilisateur de test
        User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'firstname' => 'Test',
                'lastname' => 'User',
                'password' => bcrypt('password'),
            ]
        );

        // Créer un utilisateur admin
        User::firstOrCreate(
            ['email' => 'admin@chelsy-restaurant.bj'],
            [
                'firstname' => 'Admin',
                'lastname' => 'CHELSY',
                'password' => bcrypt('admin123'),
                'is_admin' => true,
            ]
        );
    }
}
