<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\RestaurantResource;
use App\Models\Restaurant;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Restaurant",
 *     description="Endpoints pour les informations du restaurant"
 * )
 */
class RestaurantController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/restaurant",
     *     summary="Afficher les informations du restaurant",
     *     tags={"Restaurant"},
     *     @OA\Response(
     *         response=200,
     *         description="Informations du restaurant",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="restaurant", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Restaurant non trouvé"
     *     )
     * )
     */
    public function show()
    {
        $restaurant = Restaurant::where('is_active', true)->first();

        if (!$restaurant) {
            return response()->json([
                'success' => false,
                'message' => 'Restaurant non trouvé',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'restaurant' => new RestaurantResource($restaurant),
            ]
        ]);
    }
}
