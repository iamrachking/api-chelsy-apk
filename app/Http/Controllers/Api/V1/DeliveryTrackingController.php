<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DeliveryPosition;
use App\Models\Order;
use App\Models\User;
use App\Services\DeliveryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DeliveryTrackingController extends Controller
{
    protected $deliveryService;

    public function __construct(DeliveryService $deliveryService)
    {
        $this->deliveryService = $deliveryService;
    }

    /**
     * Récupérer la position du livreur pour une commande (client)
     * GET /orders/{order_id}/tracking
     */
    public function getOrderTracking(Request $request, $orderId)
    {
        try {
            $user = $request->user();
            
            // Récupérer la commande
            $order = Order::with(['driver', 'address'])->find($orderId);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commande non trouvée',
                ], 404);
            }

            // Vérifier que la commande appartient à l'utilisateur
            if ($order->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette commande ne vous appartient pas.',
                ], 403);
            }

            // ==================== VÉRIFIER LE STATUT ====================
            // La commande doit être en livraison ou livrée pour avoir un suivi
            if (!in_array($order->status, ['out_for_delivery', 'delivered'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commande non en cours de livraison',
                ], 200);
            }

            // ==================== VÉRIFIER SI UN LIVREUR EST ASSIGNÉ ====================
            if (!$order->driver_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucun livreur n\'a été assigné à cette commande',
                ], 200);
            }

            // ==================== RÉCUPÉRER LA DERNIÈRE POSITION ====================
            $latestPosition = DeliveryPosition::where('order_id', $orderId)
                ->where('driver_id', $order->driver_id)
                ->orderBy('recorded_at', 'desc')
                ->first();

            if (!$latestPosition) {
                Log::warning('Pas de position trouvée pour order', [
                    'order_id' => $orderId,
                    'driver_id' => $order->driver_id,
                ]);

                // Retourner les infos du livreur mais pas de position
                return response()->json([
                    'success' => false,
                    'message' => 'Pas de données de suivi disponibles',
                    'data' => [
                        'position' => null,
                        'driver' => $order->driver ? $this->formatDriver($order->driver) : null,
                        'eta_minutes' => null,
                        'distance_km' => null,
                        'message' => 'Localisation en cours...',
                    ],
                ], 200);
            }

            // ==================== CALCULER ETA ET DISTANCE ====================
            $etaMinutes = null;
            $distanceKm = null;

            if ($order->address && $order->address->latitude && $order->address->longitude) {
                // Calculer la distance avec la formule Haversine
                $distanceKm = $this->deliveryService->calculateDistance(
                    (float) $latestPosition->latitude,
                    (float) $latestPosition->longitude,
                    (float) $order->address->latitude,
                    (float) $order->address->longitude
                );

                // Estimer l'ETA basé sur la vitesse
                $speed = (float) $latestPosition->speed ?? 30; // km/h
                if ($distanceKm > 0 && $speed > 0) {
                    $etaMinutes = ceil(($distanceKm / $speed) * 60);
                } else if ($distanceKm > 0) {
                    // Si pas de vitesse, estimer 30 km/h
                    $etaMinutes = ceil(($distanceKm / 30) * 60);
                } else {
                    $etaMinutes = 2; // Arrivée imminente
                }
            }

            // ==================== CONSTRUIRE LA RÉPONSE ====================
            return response()->json([
                'success' => true,
                'data' => [
                    'position' => [
                        'latitude' => (float) $latestPosition->latitude,
                        'longitude' => (float) $latestPosition->longitude,
                        'accuracy' => (float) $latestPosition->accuracy,
                        'speed' => (float) $latestPosition->speed,
                        'heading' => (float) $latestPosition->heading,
                        'recorded_at' => $latestPosition->recorded_at->toIso8601String(),
                    ],
                    'driver' => $order->driver ? $this->formatDriver($order->driver) : null,
                    'eta_minutes' => $etaMinutes,
                    'distance_km' => $distanceKm ? round($distanceKm, 2) : null,
                    'message' => $etaMinutes ? "Votre livreur arrivera dans environ {$etaMinutes} minutes" : 'Votre livreur est en route',
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Erreur getOrderTracking', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du suivi',
            ], 500);
        }
    }

    /**
     * Formater les infos du livreur
     */
    private function formatDriver(User $driver): array
    {
        return [
            'id' => $driver->id,
            'name' => trim("{$driver->firstname} {$driver->lastname}"),
            'firstname' => $driver->firstname,
            'lastname' => $driver->lastname,
            'phone' => $driver->phone,
            'avatar' => $driver->avatar,
        ];
    }

    // ==================== AUTRES MÉTHODES ====================

    public function updatePosition(Request $request)
    {
        $user = $request->user();

        if (!$user->is_driver) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.',
            ], 403);
        }

        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'order_id' => 'nullable|exists:orders,id',
            'accuracy' => 'nullable|numeric|min:0',
            'speed' => 'nullable|numeric|min:0',
            'heading' => 'nullable|numeric|between:0,360',
        ]);

        if ($request->order_id) {
            $order = Order::find($request->order_id);
            if (!$order || $order->driver_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette commande ne vous est pas assignée.',
                ], 403);
            }
        }

        $position = DeliveryPosition::create([
            'driver_id' => $user->id,
            'order_id' => $request->order_id,
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'accuracy' => $validated['accuracy'] ?? null,
            'speed' => $validated['speed'] ?? null,
            'heading' => $validated['heading'] ?? null,
            'recorded_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Position enregistrée',
            'data' => ['position' => $position],
        ]);
    }

    public function getCurrentPosition(Request $request)
    {
        $user = $request->user();

        if (!$user->is_driver) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas un livreur.',
            ], 403);
        }

        $position = DeliveryPosition::where('driver_id', $user->id)
            ->latest('recorded_at')
            ->first();

        return response()->json([
            'success' => true,
            'data' => ['position' => $position],
        ]);
    }

    public function getPositionHistory(Request $request)
    {
        $user = $request->user();

        if (!$user->is_driver) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas un livreur.',
            ], 403);
        }

        $query = DeliveryPosition::where('driver_id', $user->id);

        if ($request->order_id) {
            $query->where('order_id', $request->order_id);
        }

        $minutes = $request->get('minutes', 60);
        $query->where('recorded_at', '>=', now()->subMinutes($minutes));

        $positions = $query->orderBy('recorded_at', 'desc')
            ->paginate($request->get('per_page', 50));

        return response()->json([
            'success' => true,
            'data' => [
                'positions' => $positions->items(),
                'pagination' => [
                    'current_page' => $positions->currentPage(),
                    'last_page' => $positions->lastPage(),
                    'per_page' => $positions->perPage(),
                    'total' => $positions->total(),
                ],
            ],
        ]);
    }
}