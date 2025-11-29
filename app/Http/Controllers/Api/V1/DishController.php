<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\DishResource;
use App\Models\Dish;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Plats",
 *     description="Endpoints pour la gestion des plats"
 * )
 */
class DishController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/dishes",
     *     summary="Liste des plats avec filtres",
     *     tags={"Plats"},
     *     @OA\Parameter(name="category_id", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="is_featured", in="query", @OA\Schema(type="boolean")),
     *     @OA\Parameter(name="is_new", in="query", @OA\Schema(type="boolean")),
     *     @OA\Parameter(name="is_vegetarian", in="query", @OA\Schema(type="boolean")),
     *     @OA\Parameter(name="is_specialty", in="query", @OA\Schema(type="boolean")),
     *     @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="sort_by", in="query", @OA\Schema(type="string", enum={"price", "order_count", "average_rating", "created_at"})),
     *     @OA\Parameter(name="sort_order", in="query", @OA\Schema(type="string", enum={"asc", "desc"})),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des plats",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object", @OA\Property(property="dishes", type="array", @OA\Items(type="object")))
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = Dish::with(['category', 'options.values'])
            ->where('is_available', true);

        // Filtre par catégorie
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Filtre par badge
        if ($request->has('is_featured')) {
            $query->where('is_featured', $request->is_featured);
        }
        if ($request->has('is_new')) {
            $query->where('is_new', $request->is_new);
        }
        if ($request->has('is_vegetarian')) {
            $query->where('is_vegetarian', $request->is_vegetarian);
        }
        if ($request->has('is_specialty')) {
            $query->where('is_specialty', $request->is_specialty);
        }

        // Recherche
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Tri
        $sortBy = $request->get('sort_by', 'order_count');
        $sortOrder = $request->get('sort_order', 'desc');
        
        if (in_array($sortBy, ['price', 'order_count', 'average_rating', 'created_at'])) {
            $query->orderBy($sortBy, $sortOrder);
        }

        $dishes = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => [
                'dishes' => DishResource::collection($dishes->items()),
                'pagination' => [
                    'current_page' => $dishes->currentPage(),
                    'last_page' => $dishes->lastPage(),
                    'per_page' => $dishes->perPage(),
                    'total' => $dishes->total(),
                ]
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/dishes/{id}",
     *     summary="Détails d'un plat",
     *     tags={"Plats"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Détails du plat",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object", @OA\Property(property="dish", type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Plat non trouvé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Plat non trouvé")
     *         )
     *     )
     * )
     */
    public function show($id)
    {
        $dish = Dish::with([
            'category',
            'options.values',
            'reviews' => function($query) {
                $query->where('is_approved', true)
                      ->orderBy('created_at', 'desc')
                      ->limit(10)
                      ->with('user');
            }
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'dish' => new DishResource($dish),
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/dishes/featured",
     *     summary="Plats du jour / Suggestions du chef",
     *     tags={"Plats"},
     *     @OA\Response(
     *         response=200,
     *         description="Liste des plats mis en avant",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object", @OA\Property(property="dishes", type="array", @OA\Items(type="object")))
     *         )
     *     )
     * )
     */
    public function featured(Request $request)
    {
        $dishes = Dish::with(['category'])
            ->where('is_available', true)
            ->where(function($query) {
                $query->where('is_featured', true)
                      ->orWhere('is_new', true)
                      ->orWhere('is_specialty', true);
            })
            ->orderBy('order_count', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'dishes' => DishResource::collection($dishes),
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/dishes/popular",
     *     summary="Plats populaires",
     *     tags={"Plats"},
     *     @OA\Response(
     *         response=200,
     *         description="Liste des plats populaires",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object", @OA\Property(property="dishes", type="array", @OA\Items(type="object")))
     *         )
     *     )
     * )
     */
    public function popular(Request $request)
    {
        $dishes = Dish::with(['category'])
            ->where('is_available', true)
            ->orderBy('order_count', 'desc')
            ->orderBy('average_rating', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'dishes' => DishResource::collection($dishes),
            ]
        ]);
    }
}
