<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CreateReviewRequest;
use App\Http\Resources\Api\V1\ReviewResource;
use App\Models\Review;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Avis",
 *     description="Endpoints pour la gestion des avis et notations"
 * )
 */
class ReviewController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/v1/reviews",
     *     summary="Créer un avis",
     *     tags={"Avis"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"rating"},
     *             @OA\Property(property="order_id", type="integer", nullable=true, example=1),
     *             @OA\Property(property="dish_id", type="integer", nullable=true, example=1),
     *             @OA\Property(property="rating", type="integer", minimum=1, maximum=5, example=5),
     *             @OA\Property(property="comment", type="string", example="Excellent plat !"),
     *             @OA\Property(property="images", type="array", @OA\Items(type="string"), example={})
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Avis créé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Avis soumis avec succès (en attente de modération)"),
     *             @OA\Property(property="data", type="object", @OA\Property(property="review", type="object"))
     *         )
     *     )
     * )
     */
    public function store(CreateReviewRequest $request)
    {
        $review = Review::create([
            'user_id' => $request->user()->id,
            'order_id' => $request->order_id,
            'dish_id' => $request->dish_id,
            'type' => $request->dish_id ? 'dish' : 'restaurant',
            'rating' => $request->rating,
            'comment' => $request->comment,
            'images' => $request->images,
            'is_approved' => false, // Nécessite modération
        ]);

        // Mettre à jour la note moyenne du plat si c'est un avis sur un plat
        if ($request->dish_id) {
            $dish = \App\Models\Dish::find($request->dish_id);
            if ($dish) {
                $dish->increment('review_count');
                $dish->update([
                    'average_rating' => Review::where('dish_id', $dish->id)
                        ->where('is_approved', true)
                        ->avg('rating') ?? 0
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Avis soumis avec succès (en attente de modération)',
            'data' => [
                'review' => new ReviewResource($review->load('user')),
            ]
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/dishes/{dishId}/reviews",
     *     summary="Liste des avis approuvés d'un plat",
     *     tags={"Avis"},
     *     @OA\Parameter(
     *         name="dishId",
     *         in="path",
     *         required=true,
     *         description="ID du plat",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Numéro de page",
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des avis",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object", @OA\Property(property="reviews", type="array", @OA\Items(type="object")))
     *         )
     *     )
     * )
     */
    public function dishReviews($dishId)
    {
        $reviews = Review::where('dish_id', $dishId)
            ->where('is_approved', true)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => [
                'reviews' => ReviewResource::collection($reviews),
            ]
        ]);
    }
}
