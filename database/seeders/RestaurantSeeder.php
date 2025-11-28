<?php

namespace Database\Seeders;

use App\Models\Restaurant;
use Illuminate\Database\Seeder;

class RestaurantSeeder extends Seeder
{
    public function run(): void
    {
        Restaurant::firstOrCreate(
            ['email' => 'contact@chelsy-restaurant.bj'],
            [
                'name' => 'CHELSY Restaurant',
            'description' => 'Un restaurant gastronomique offrant une cuisine raffinée et authentique.',
            'history' => 'CHELSY Restaurant a été fondé avec la passion de partager une cuisine d\'exception. Depuis notre ouverture, nous nous engageons à offrir une expérience culinaire unique qui allie tradition et modernité.',
            'values' => 'Qualité, Authenticité, Service client exceptionnel, Respect des produits locaux',
            'chef_name' => 'Chef Chelsy',
            'team_description' => 'Notre équipe est composée de professionnels passionnés dédiés à vous offrir le meilleur service.',
            'phone' => '+229 12 34 56 78',
            'email' => 'contact@chelsy-restaurant.bj',
            'address' => 'Cotonou, Bénin',
            'latitude' => 6.372477,
            'longitude' => 2.354006,
            'opening_hours' => [
                'monday' => ['open' => '09:00', 'close' => '22:00'],
                'tuesday' => ['open' => '09:00', 'close' => '22:00'],
                'wednesday' => ['open' => '09:00', 'close' => '22:00'],
                'thursday' => ['open' => '09:00', 'close' => '22:00'],
                'friday' => ['open' => '09:00', 'close' => '23:00'],
                'saturday' => ['open' => '10:00', 'close' => '23:00'],
                'sunday' => ['open' => '11:00', 'close' => '21:00'],
            ],
            'social_media' => [
                'facebook' => 'https://facebook.com/chelsy-restaurant',
                'instagram' => 'https://instagram.com/chelsy_restaurant',
                'twitter' => 'https://twitter.com/chelsy_restaurant',
            ],
            'delivery_radius_km' => 10,
            'delivery_fee_base' => 1000.00,
            'delivery_fee_per_km' => 500.00,
            'minimum_order_amount' => 5000.00,
            'is_active' => true,
        ]);
    }
}
