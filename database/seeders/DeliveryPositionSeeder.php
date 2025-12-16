<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Order;
use App\Models\User;
use App\Models\DeliveryPosition;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DeliveryPositionSeeder extends Seeder
{
    /**
     * ID du livreur fictif
     */
    private const DRIVER_ID = 7;

    /**
     * Points de parcours fictifs (simulation d'une route à Cotonou)
     * Route réaliste : Restaurant → Client
     */
    private const DELIVERY_ROUTE = [
        ['lat' => 6.4969, 'lon' => 2.6289, 'speed' => 0, 'name' => 'Restaurant (Départ)'],
        ['lat' => 6.4955, 'lon' => 2.6305, 'speed' => 25, 'name' => 'Sur la route 1'],
        ['lat' => 6.4938, 'lon' => 2.6320, 'speed' => 35, 'name' => 'Sur la route 2'],
        ['lat' => 6.4920, 'lon' => 2.6340, 'speed' => 40, 'name' => 'Sur la route 3'],
        ['lat' => 6.4900, 'lon' => 2.6355, 'speed' => 35, 'name' => 'Approche adresse'],
        ['lat' => 6.4890, 'lon' => 2.6360, 'speed' => 15, 'name' => 'Destination'],
    ];

    public function run(): void
    {
        $this->command->info('🚀 Démarrage du seeder de positions de livraison...');

        // ==================== VÉRIFIER LE LIVREUR ====================
        $driver = User::find(self::DRIVER_ID);
        if (!$driver) {
            $this->command->error('❌ Livreur avec ID ' . self::DRIVER_ID . ' non trouvé !');
            return;
        }

        // S'assurer que c'est bien un livreur
        if (!$driver->is_driver) {
            $driver->update(['is_driver' => true]);
            $this->command->info('✅ Utilisateur ID ' . self::DRIVER_ID . ' marqué comme livreur');
        }

        $this->command->info('👤 Livreur: ' . $driver->firstname . ' ' . $driver->lastname);
        $this->command->info('📧 Email: ' . $driver->email);

        // ==================== RÉCUPÉRER LES COMMANDES ====================
        // Commandes en livraison OU déjà livrées
        $orders = Order::whereIn('status', ['out_for_delivery', 'delivered'])
            ->orWhere('type', 'delivery')
            ->limit(10)
            ->get();

        if ($orders->isEmpty()) {
            $this->command->warn('⚠️ Aucune commande en livraison trouvée');
            return;
        }

        $this->command->info("📦 Nombre de commandes à traiter: {$orders->count()}");

        // ==================== TRAITER CHAQUE COMMANDE ====================
        foreach ($orders as $order) {
            try {
                // Assigner le livreur
                $order->update([
                    'driver_id' => self::DRIVER_ID,
                    'status' => 'out_for_delivery', // Mettre en livraison
                ]);

                // Supprimer les anciennes positions
                DeliveryPosition::where('order_id', $order->id)->delete();

                // Créer les nouvelles positions fictives
                $this->createFictivePositions($order);

                $this->command->info("✅ Commande #{$order->order_number} - Livreur assigné et positions créées");
            } catch (\Exception $e) {
                $this->command->error("❌ Erreur pour commande #{$order->id}: " . $e->getMessage());
                Log::error('Erreur seeder position', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->command->info('');
        $this->command->info('✨ Seeder terminé avec succès !');
        $this->command->info('📍 Positions créées avec succès');
        $this->command->info('🎯 Tous les commandes sont maintenant assignées au livreur ' . self::DRIVER_ID);
    }

    /**
     * Créer des positions fictives réalistes pour une commande
     */
    private function createFictivePositions(Order $order): void
    {
        // Le trajet prend environ 12 minutes (2 minutes par étape)
        $startTime = now()->subMinutes(count(self::DELIVERY_ROUTE) * 2);
        $positions = [];

        foreach (self::DELIVERY_ROUTE as $index => $point) {
            $positions[] = [
                'driver_id' => self::DRIVER_ID,
                'order_id' => $order->id,
                'latitude' => $point['lat'],
                'longitude' => $point['lon'],
                'accuracy' => rand(5, 15), // Précision GPS en mètres
                'speed' => $point['speed'], // Vitesse réaliste
                'heading' => $this->calculateHeading($index), // Direction réaliste
                'recorded_at' => $startTime->copy()->addMinutes($index * 2),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Insérer en masse pour les performances
        DeliveryPosition::insert($positions);
    }

    /**
     * Calculer une direction réaliste basée sur le trajet
     */
    private function calculateHeading($currentIndex): float
    {
        if ($currentIndex === 0) {
            return 0; // Au départ, direction aléatoire
        }

        $previous = self::DELIVERY_ROUTE[$currentIndex - 1];
        $current = self::DELIVERY_ROUTE[$currentIndex];

        // Calculer l'angle entre deux points
        $dLon = $current['lon'] - $previous['lon'];
        $dLat = $current['lat'] - $previous['lat'];

        $heading = rad2deg(atan2($dLon, $dLat));
        
        // Normaliser entre 0 et 360
        if ($heading < 0) {
            $heading += 360;
        }

        return round($heading, 2);
    }
}