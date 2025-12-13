<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AddToCartRequest;
use App\Http\Resources\Api\V1\CartResource;
use App\Http\Resources\Api\V1\CartItemResource;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Dish;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Panier",
 *     description="Endpoints pour la gestion du panier"
 * )
 */
class CartController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/cart",
     *     summary="Récupérer le panier de l'utilisateur",
     *     tags={"Panier"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Panier récupéré avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object", @OA\Property(property="cart", type="object"))
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $cart = Cart::with(['items.dish'])
            ->where('user_id', $user->id)
            ->first();

        if (!$cart) {
            $cart = Cart::create(['user_id' => $user->id]);
        }

        $cart->load('items.dish');

        return response()->json([
            'success' => true,
            'data' => [
                'cart' => [
                    'id' => $cart->id,
                    'user_id' => $cart->user_id,
                    'session_id' => $cart->session_id,
                    'items' => CartItemResource::collection($cart->items),
                    'subtotal' => (float) $cart->subtotal,
                    'total_items' => (int) $cart->total_items,
                    'created_at' => $cart->created_at->toIso8601String(),
                    'updated_at' => $cart->updated_at->toIso8601String(),
                ]
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/cart/items",
     *     summary="Ajouter un plat au panier",
     *     tags={"Panier"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"dish_id", "quantity"},
     *             @OA\Property(property="dish_id", type="integer", example=1),
     *             @OA\Property(property="quantity", type="integer", example=2),
     *             @OA\Property(property="selected_options", type="object", example={"1": 1, "2": 3}),
     *             @OA\Property(property="special_instructions", type="string", example="Sans oignons")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Plat ajouté au panier",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Plat ajouté au panier"),
     *             @OA\Property(property="data", type="object", @OA\Property(property="cart_item", type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation ou plat non disponible"
     *     )
     * )
     */
    public function addItem(AddToCartRequest $request)
    {
        $dish = Dish::findOrFail($request->dish_id);

        if (!$dish->is_available) {
            return response()->json([
                'success' => false,
                'message' => 'Ce plat n\'est pas disponible',
            ], 422);
        }

        $user = $request->user();
        $cart = Cart::firstOrCreate(['user_id' => $user->id]);

        // Calculer le prix avec les options
        $unitPrice = $dish->final_price;
        if ($request->selected_options && is_array($request->selected_options)) {
            $options = $dish->options()->with('values')->get();
            foreach ($request->selected_options as $optionId => $valueId) {
                $option = $options->find($optionId);
                if ($option) {
                    $value = $option->values->find($valueId);
                    if ($value) {
                        $unitPrice += $value->price_modifier;
                    }
                }
            }
        }

        $cartItem = CartItem::updateOrCreate(
            [
                'cart_id' => $cart->id,
                'dish_id' => $dish->id,
                'selected_options' => $request->selected_options,
            ],
            [
                'quantity' => $request->quantity,
                'unit_price' => $unitPrice,
                'special_instructions' => $request->special_instructions,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Plat ajouté au panier',
            'data' => [
                'cart_item' => new CartItemResource($cartItem->load('dish')),
            ]
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/cart/items/{id}",
     *     summary="Mettre à jour un article du panier",
     *     tags={"Panier"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de l'article du panier",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"quantity"},
     *             @OA\Property(property="quantity", type="integer", example=3)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Article mis à jour",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Article mis à jour"),
     *             @OA\Property(property="data", type="object", @OA\Property(property="cart_item", type="object"))
     *         )
     *     )
     * )
     */
    public function updateItem(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $cartItem = CartItem::whereHas('cart', function($query) use ($user) {
            $query->where('user_id', $user->id);
        })->findOrFail($id);

        $cartItem->update([
            'quantity' => $request->quantity,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Article mis à jour',
            'data' => [
                'cart_item' => new CartItemResource($cartItem->load('dish')),
            ]
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/cart/items/{id}",
     *     summary="Supprimer un article du panier",
     *     tags={"Panier"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de l'article du panier",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Article supprimé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Article supprimé du panier")
     *         )
     *     )
     * )
     */
    public function removeItem($id)
    {
        $user = request()->user();
        $cartItem = CartItem::whereHas('cart', function($query) use ($user) {
            $query->where('user_id', $user->id);
        })->findOrFail($id);

        $cartItem->delete();

        return response()->json([
            'success' => true,
            'message' => 'Article supprimé du panier',
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/cart",
     *     summary="Vider le panier",
     *     tags={"Panier"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Panier vidé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Panier vidé")
     *         )
     *     )
     * )
     */
    public function clear(Request $request)
    {
        $user = $request->user();
        $cart = Cart::where('user_id', $user->id)->first();

        if ($cart) {
            $cart->items()->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Panier vidé',
        ]);
    }
}