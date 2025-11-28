<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Dish;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DishSeeder extends Seeder
{
    public function run(): void
    {
        $entrees = Category::where('slug', 'entrees')->first();
        $plats = Category::where('slug', 'plats-principaux')->first();
        $desserts = Category::where('slug', 'desserts')->first();
        $boissons = Category::where('slug', 'boissons')->first();

        // Entrées
        if ($entrees) {
            $entreesDishes = [
                [
                    'name' => 'Salade César',
                    'description' => 'Salade fraîche avec poulet grillé, parmesan et croûtons.',
                    'price' => 3500,
                    'preparation_time_minutes' => 15,
                    'is_vegetarian' => false,
                ],
                [
                    'name' => 'Soupe du Jour',
                    'description' => 'Soupe maison préparée quotidiennement avec des légumes frais.',
                    'price' => 2500,
                    'preparation_time_minutes' => 10,
                    'is_vegetarian' => true,
                ],
                [
                    'name' => 'Bruschetta Italienne',
                    'description' => 'Pain grillé avec tomates fraîches, basilic et mozzarella.',
                    'price' => 3000,
                    'preparation_time_minutes' => 12,
                    'is_vegetarian' => true,
                ],
            ];

            foreach ($entreesDishes as $dish) {
                Dish::firstOrCreate(
                    ['slug' => Str::slug($dish['name'])],
                    [
                        'category_id' => $entrees->id,
                        'name' => $dish['name'],
                    'description' => $dish['description'],
                    'price' => $dish['price'],
                    'images' => [],
                    'preparation_time_minutes' => $dish['preparation_time_minutes'],
                    'is_available' => true,
                    'is_vegetarian' => $dish['is_vegetarian'],
                    ]
                );
            }
        }

        // Plats Principaux
        if ($plats) {
            $platsDishes = [
                [
                    'name' => 'Poulet Yassa',
                    'description' => 'Poulet mariné dans une sauce à l\'oignon et citron, servi avec du riz.',
                    'price' => 6500,
                    'preparation_time_minutes' => 30,
                    'is_featured' => true,
                    'is_specialty' => true,
                ],
                [
                    'name' => 'Grillade Mixte',
                    'description' => 'Assortiment de viandes grillées avec frites et légumes.',
                    'price' => 8500,
                    'preparation_time_minutes' => 35,
                    'is_featured' => true,
                ],
                [
                    'name' => 'Poisson Braisé',
                    'description' => 'Poisson frais braisé avec épices, servi avec attiéké et légumes.',
                    'price' => 7000,
                    'preparation_time_minutes' => 25,
                    'is_specialty' => true,
                ],
                [
                    'name' => 'Riz au Poisson',
                    'description' => 'Riz parfumé avec poisson frais et légumes de saison.',
                    'price' => 5500,
                    'preparation_time_minutes' => 20,
                ],
            ];

            foreach ($platsDishes as $dish) {
                Dish::firstOrCreate(
                    ['slug' => Str::slug($dish['name'])],
                    [
                        'category_id' => $plats->id,
                        'name' => $dish['name'],
                    'description' => $dish['description'],
                    'price' => $dish['price'],
                    'images' => [],
                    'preparation_time_minutes' => $dish['preparation_time_minutes'],
                    'is_available' => true,
                    'is_featured' => $dish['is_featured'] ?? false,
                    'is_specialty' => $dish['is_specialty'] ?? false,
                    ]
                );
            }
        }

        // Desserts
        if ($desserts) {
            $dessertsDishes = [
                [
                    'name' => 'Tiramisu Maison',
                    'description' => 'Dessert italien classique préparé avec amour.',
                    'price' => 3000,
                    'preparation_time_minutes' => 5,
                    'is_vegetarian' => true,
                ],
                [
                    'name' => 'Fondant au Chocolat',
                    'description' => 'Gâteau au chocolat fondant servi avec glace vanille.',
                    'price' => 3500,
                    'preparation_time_minutes' => 8,
                    'is_vegetarian' => true,
                ],
                [
                    'name' => 'Salade de Fruits Frais',
                    'description' => 'Assortiment de fruits de saison frais et sucrés.',
                    'price' => 2500,
                    'preparation_time_minutes' => 5,
                    'is_vegetarian' => true,
                ],
            ];

            foreach ($dessertsDishes as $dish) {
                Dish::firstOrCreate(
                    ['slug' => Str::slug($dish['name'])],
                    [
                        'category_id' => $desserts->id,
                        'name' => $dish['name'],
                    'description' => $dish['description'],
                    'price' => $dish['price'],
                    'images' => [],
                    'preparation_time_minutes' => $dish['preparation_time_minutes'],
                    'is_available' => true,
                    'is_vegetarian' => $dish['is_vegetarian'],
                    ]
                );
            }
        }

        // Boissons
        if ($boissons) {
            $boissonsDishes = [
                [
                    'name' => 'Jus de Fruits Naturel',
                    'description' => 'Jus de fruits pressés frais (orange, ananas, mangue).',
                    'price' => 2000,
                    'preparation_time_minutes' => 5,
                    'is_vegetarian' => true,
                ],
                [
                    'name' => 'Café Expresso',
                    'description' => 'Café expresso italien de qualité.',
                    'price' => 1500,
                    'preparation_time_minutes' => 3,
                    'is_vegetarian' => true,
                ],
                [
                    'name' => 'Thé à la Menthe',
                    'description' => 'Thé vert à la menthe fraîche, servi chaud.',
                    'price' => 1500,
                    'preparation_time_minutes' => 5,
                    'is_vegetarian' => true,
                ],
                [
                    'name' => 'Eau Minérale',
                    'description' => 'Eau minérale naturelle (50cl).',
                    'price' => 1000,
                    'preparation_time_minutes' => 1,
                    'is_vegetarian' => true,
                ],
            ];

            foreach ($boissonsDishes as $dish) {
                Dish::firstOrCreate(
                    ['slug' => Str::slug($dish['name'])],
                    [
                        'category_id' => $boissons->id,
                        'name' => $dish['name'],
                    'description' => $dish['description'],
                    'price' => $dish['price'],
                    'images' => [],
                    'preparation_time_minutes' => $dish['preparation_time_minutes'],
                    'is_available' => true,
                    'is_vegetarian' => $dish['is_vegetarian'],
                    ]
                );
            }
        }
    }
}
