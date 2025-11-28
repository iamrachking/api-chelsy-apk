<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Entrées',
                'description' => 'Découvrez nos délicieuses entrées pour commencer votre repas en beauté.',
                'order' => 1,
            ],
            [
                'name' => 'Plats Principaux',
                'description' => 'Nos plats principaux préparés avec des ingrédients frais et de qualité.',
                'order' => 2,
            ],
            [
                'name' => 'Desserts',
                'description' => 'Terminez votre repas avec nos desserts maison gourmands.',
                'order' => 3,
            ],
            [
                'name' => 'Boissons',
                'description' => 'Rafraîchissez-vous avec nos boissons chaudes et froides.',
                'order' => 4,
            ],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(
                ['slug' => Str::slug($category['name'])],
                [
                    'name' => $category['name'],
                    'description' => $category['description'],
                    'order' => $category['order'],
                    'is_active' => true,
                ]
            );
        }
    }
}
