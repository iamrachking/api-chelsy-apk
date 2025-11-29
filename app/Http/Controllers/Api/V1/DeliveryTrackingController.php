<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DeliveryPosition;
use App\Models\Order;
use App\Models\User;
use App\Services\DeliveryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Suivi GPS Livreur",
 *     description="Endpoints pour le suivi GPS des livreurs et le calcul de l'ETA"
 * )
 */
class DeliveryTrackingController extends Controller
{
    protected $deliveryService;

    public function __construct(DeliveryService $deliveryService)
    {
        $this->deliveryService = $deliveryService;
    }

    /**
     * @OA\Post(
     *     path="/api/v1/delivery/position",
     *     summary="Mettre à jour la position GPS du livreur",
     *     tags={"Suivi GPS Livreur"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"latitude", "longitude"},
     *             @OA\Property(property="latitude", type="number", format="float", example=6.372477, description="Latitude"),
     *             @OA\Property(property="longitude", type="number", format="float", example=2.354006, description="Longitude"),
     *             @OA\Property(property="order_id", type="integer", nullable=true, example=1, description="ID de la commande en cours de livraison"),
     *             @OA\Property(property="accuracy", type="number", format="float", nullable=true, example=10.5, description="Précision en mètres"),
     *             @OA\Property(property="speed", type="number", format="float", nullable=true, example=45.0, description="Vitesse en km/h"),
     *             @OA\Property(property="heading", type="number", format="float", nullable=true, example=180.0, description="Direction en degrés (0-360)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Position enregistrée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Position enregistrée avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="position", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Utilisateur n'est pas un livreur",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Vous n'êtes pas autorisé à effectuer cette action")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation"
     *     )
     * )
     */
    public function updatePosition(Request $request)
    {
        $user = $request->user();

        // Vérifier que l'utilisateur est un livreur
        if (!$user->is_driver) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à effectuer cette action. Seuls les livreurs peuvent mettre à jour leur position.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'order_id' => 'nullable|exists:orders,id',
            'accuracy' => 'nullable|numeric|min:0',
            'speed' => 'nullable|numeric|min:0',
            'heading' => 'nullable|numeric|between:0,360',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        // Si un order_id est fourni, vérifier que le livreur est assigné à cette commande
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
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'accuracy' => $request->accuracy,
            'speed' => $request->speed,
            'heading' => $request->heading,
            'recorded_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Position enregistrée avec succès',
            'data' => [
                'position' => $position,
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/delivery/position/current",
     *     summary="Récupérer la position actuelle du livreur connecté",
     *     tags={"Suivi GPS Livreur"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Position actuelle du livreur",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="position", type="object", nullable=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Utilisateur n'est pas un livreur"
     *     )
     * )
     */
    public function getCurrentPosition(Request $request)
    {
        $user = $request->user();

        if (!$user->is_driver) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.',
            ], 403);
        }

        $position = DeliveryPosition::where('driver_id', $user->id)
            ->latest('recorded_at')
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'position' => $position,
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/delivery/position/history",
     *     summary="Récupérer l'historique des positions du livreur",
     *     tags={"Suivi GPS Livreur"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="order_id",
     *         in="query",
     *         description="Filtrer par commande",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="minutes",
     *         in="query",
     *         description="Nombre de minutes à remonter (défaut: 60)",
     *         @OA\Schema(type="integer", default=60)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Nombre d'éléments par page",
     *         @OA\Schema(type="integer", default=50)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Historique des positions",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="positions", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="pagination", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Utilisateur n'est pas un livreur"
     *     )
     * )
     */
    public function getPositionHistory(Request $request)
    {
        $user = $request->user();

        if (!$user->is_driver) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à effectuer cette action.',
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
                ]
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/orders/{order_id}/tracking",
     *     summary="Récupérer la position du livreur pour une commande (client)",
     *     tags={"Suivi GPS Livreur"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="order_id",
     *         in="path",
     *         required=true,
     *         description="ID de la commande",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Position du livreur",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="position", type="object", nullable=true),
     *                 @OA\Property(property="driver", type="object", nullable=true),
     *                 @OA\Property(property="eta_minutes", type="integer", nullable=true, description="Temps estimé d'arrivée en minutes"),
     *                 @OA\Property(property="distance_km", type="number", format="float", nullable=true, description="Distance en kilomètres")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Commande n'appartient pas à l'utilisateur"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Commande non trouvée"
     *     )
     * )
     */
    public function getOrderTracking(Request $request, $orderId)
    {
        $user = $request->user();
        $order = Order::with(['driver', 'address'])->findOrFail($orderId);

        // Vérifier que la commande appartient à l'utilisateur
        if ($order->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Cette commande ne vous appartient pas.',
            ], 403);
        }

        // Vérifier que la commande est en cours de livraison
        if ($order->status !== 'out_for_delivery') {
            return response()->json([
                'success' => true,
                'data' => [
                    'position' => null,
                    'driver' => $order->driver,
                    'eta_minutes' => null,
                    'distance_km' => null,
                    'message' => 'La commande n\'est pas encore en cours de livraison.',
                ]
            ]);
        }

        // Récupérer la dernière position du livreur pour cette commande
        $position = DeliveryPosition::where('order_id', $orderId)
            ->where('driver_id', $order->driver_id)
            ->latest('recorded_at')
            ->first();

        $etaMinutes = null;
        $distanceKm = null;

        // Calculer l'ETA et la distance si on a la position du livreur et l'adresse de livraison
        if ($position && $order->address && $order->address->latitude && $order->address->longitude) {
            $distanceKm = $this->deliveryService->calculateDistance(
                $position->latitude,
                $position->longitude,
                $order->address->latitude,
                $order->address->longitude
            );

            // Estimation basique : 30 km/h en moyenne en ville
            // On peut améliorer avec la vitesse réelle si disponible
            $averageSpeed = $position->speed ?? 30; // km/h
            if ($averageSpeed > 0) {
                $etaMinutes = round(($distanceKm / $averageSpeed) * 60);
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'position' => $position,
                'driver' => $order->driver ? [
                    'id' => $order->driver->id,
                    'name' => $order->driver->name,
                    'phone' => $order->driver->phone,
                ] : null,
                'eta_minutes' => $etaMinutes,
                'distance_km' => $distanceKm ? round($distanceKm, 2) : null,
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/delivery/drivers/available",
     *     summary="Récupérer la liste des livreurs disponibles avec leur position (admin)",
     *     tags={"Suivi GPS Livreur"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Liste des livreurs disponibles",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="drivers", type="array", @OA\Items(type="object"))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accès non autorisé (admin uniquement)"
     *     )
     * )
     */
    public function getAvailableDrivers(Request $request)
    {
        $user = $request->user();

        if (!$user->is_admin) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé.',
            ], 403);
        }

        // Récupérer tous les livreurs avec leur dernière position
        $drivers = User::where('is_driver', true)
            ->where('is_blocked', false)
            ->get()
            ->map(function($driver) {
                $latestPosition = DeliveryPosition::where('driver_id', $driver->id)
                    ->latest('recorded_at')
                    ->first();

                return [
                    'id' => $driver->id,
                    'name' => $driver->name,
                    'phone' => $driver->phone,
                    'email' => $driver->email,
                    'position' => $latestPosition ? [
                        'latitude' => $latestPosition->latitude,
                        'longitude' => $latestPosition->longitude,
                        'recorded_at' => $latestPosition->recorded_at,
                        'speed' => $latestPosition->speed,
                        'heading' => $latestPosition->heading,
                    ] : null,
                    'is_available' => !$latestPosition || $latestPosition->recorded_at->diffInMinutes(now()) < 10, // Disponible si dernière position < 10 min
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'drivers' => $drivers,
            ]
        ]);
    }
}

