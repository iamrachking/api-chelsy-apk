<?php

namespace App\Services;

use App\Models\Order;
use App\Models\DeliveryPosition;
use Illuminate\Support\Facades\Log;

class DeliveryAssignmentService
{
    public function assignDriverToOrder(Order $order): void
    {
        try {
            $driverId = 7; // 🔒 livreur fixe

            $order->update([
                'driver_id' => $driverId,
                'status' => 'out_for_delivery',
            ]);

            // Position GPS fictive (restaurant)
            DeliveryPosition::create([
                'order_id' => $order->id,
                'driver_id' => $driverId,
                'latitude' => $order->restaurant->latitude ?? 6.3702928,
                'longitude' => $order->restaurant->longitude ?? 2.3912362,
                'accuracy' => 10,
                'speed' => 30,
                'heading' => 90,
                'recorded_at' => now(),
            ]);

            Log::info('✅ Livreur assigné automatiquement', [
                'order_id' => $order->id,
                'driver_id' => $driverId,
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur assignation livreur', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
