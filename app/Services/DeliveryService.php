<?php

namespace App\Services;

use App\Models\Address;
use App\Models\Restaurant;

class DeliveryService
{
    /**
     * Calculer la distance entre deux points (formule de Haversine)
     * 
     * @param float $lat1 Latitude du point 1
     * @param float $lon1 Longitude du point 1
     * @param float $lat2 Latitude du point 2
     * @param float $lon2 Longitude du point 2
     * @return float Distance en kilomètres
     */
    public function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // Rayon de la Terre en km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c, 2);
    }

    /**
     * Calculer les frais de livraison
     * 
     * @param Restaurant $restaurant
     * @param Address $address
     * @return array ['distance' => float, 'fee' => float, 'in_range' => bool]
     */
    public function calculateDeliveryFee(Restaurant $restaurant, Address $address): array
    {
        if (!$restaurant->latitude || !$restaurant->longitude) {
            return [
                'distance' => 0,
                'fee' => $restaurant->delivery_fee_base,
                'in_range' => false,
            ];
        }

        $distance = $this->calculateDistance(
            $restaurant->latitude,
            $restaurant->longitude,
            $address->latitude,
            $address->longitude
        );

        $inRange = $distance <= $restaurant->delivery_radius_km;

        if (!$inRange) {
            return [
                'distance' => $distance,
                'fee' => 0,
                'in_range' => false,
            ];
        }

        $fee = $restaurant->delivery_fee_base + ($distance * $restaurant->delivery_fee_per_km);

        return [
            'distance' => $distance,
            'fee' => round($fee, 2),
            'in_range' => true,
        ];
    }

    /**
     * Vérifier si une adresse est dans la zone de livraison
     */
    public function isInDeliveryZone(Restaurant $restaurant, Address $address): bool
    {
        $result = $this->calculateDeliveryFee($restaurant, $address);
        return $result['in_range'];
    }
}


