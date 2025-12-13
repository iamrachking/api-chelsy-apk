<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\FavoriteResource;
use App\Models\Favorite;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Favoris",
 *     description="Endpoints pour la gestion des plats favoris"
 * )
 */
class FavoriteController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/favorites",
     *     summary="Liste des plats favoris",
     *     tags={"Favoris"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Liste des favoris",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object", @OA\Property(property="favorites", type="array", @OA\Items(type="object")))
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $favorites = $request->user()->favorites()
            ->with('dish.category')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'favorites' => FavoriteResource::collection($favorites),
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/favorites",
     *     summary="Ajouter un plat aux favoris",
     *     tags={"Favoris"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"dish_id"},
     *             @OA\Property(property="dish_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Plat ajouté aux favoris",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Ajouté aux favoris"),
     *             @OA\Property(property="data", type="object", @OA\Property(property="favorite", type="object"))
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'dish_id' => 'required|exists:dishes,id',
        ]);

        $favorite = Favorite::firstOrCreate([
            'user_id' => $request->user()->id,
            'dish_id' => $request->dish_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Ajouté aux favoris',
            'data' => [
                'favorite' => new FavoriteResource($favorite->load('dish')),
            ]
        ], 201);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/favorites/{id}",
     *     summary="Retirer un plat des favoris",
     *     tags={"Favoris"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID du favori",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Plat retiré des favoris",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Retiré des favoris")
     *         )
     *     )
     * )
     */
    public function destroy(Request $request, $id)
    {
        $favorite = $request->user()->favorites()->findOrFail($id);
        $favorite->delete();

        return response()->json([
            'success' => true,
            'message' => 'Retiré des favoris',
        ]);
    }
}