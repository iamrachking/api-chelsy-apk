<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CreateOrderRequest;
use App\Http\Resources\Api\V1\OrderResource;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Restaurant;
use App\Services\DeliveryService;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Commandes",
 *     description="Endpoints pour la gestion des commandes"
 * )
 */
class OrderController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/orders",
     *     summary="Liste des commandes de l'utilisateur",
     *     tags={"Commandes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Nombre d'éléments par page",
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des commandes",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object", @OA\Property(property="orders", type="array", @OA\Items(type="object")))
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $orders = $request->user()->orders()
            ->with(['restaurant', 'address', 'items.dish', 'payment'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => [
                'orders' => OrderResource::collection($orders),
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/orders",
     *     summary="Créer une nouvelle commande",
     *     tags={"Commandes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"type", "payment_method"},
     *             @OA\Property(property="type", type="string", enum={"delivery", "pickup"}, example="delivery", description="Type de commande : delivery (livraison) ou pickup (à emporter)"),
     *             @OA\Property(property="address_id", type="integer", nullable=true, example=1, description="ID de l'adresse (requis si type=delivery)"),
     *             @OA\Property(property="payment_method", type="string", enum={"card", "cash", "mobile_money"}, example="card"),
     *             @OA\Property(property="mobile_money_provider", type="string", nullable=true, enum={"MTN", "Moov"}, description="Fournisseur Mobile Money (requis si payment_method=mobile_money)"),
     *             @OA\Property(property="mobile_money_number", type="string", nullable=true, example="+229 12 34 56 78", description="Numéro Mobile Money (requis si payment_method=mobile_money)"),
     *             @OA\Property(property="promo_code", type="string", nullable=true, example="PROMO10"),
     *             @OA\Property(property="scheduled_at", type="string", format="datetime", nullable=true, example="2025-11-27 19:00:00"),
     *             @OA\Property(property="special_instructions", type="string", nullable=true, example="Sonner 2 fois", description="Instructions spéciales pour la commande")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Commande créée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Commande créée avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="order", type="object"),
     *                 @OA\Property(property="payment", type="object", nullable=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation"
     *     )
     * )
     */
    public function store(CreateOrderRequest $request)
    {

        $user = $request->user();
        $cart = Cart::with('items.dish')->where('user_id', $user->id)->first();

        if (!$cart) {
            return response()->json([
                'success' => false,
                'message' => 'Votre panier est vide',
            ], 422);
        }

        $cart->load('items.dish');
        
        if ($cart->items->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Votre panier est vide',
            ], 422);
        }

        $restaurant = Restaurant::where('is_active', true)->firstOrFail();

        // Vérifier le minimum de commande
        $subtotal = $cart->subtotal;
        if ($subtotal < $restaurant->minimum_order_amount) {
            return response()->json([
                'success' => false,
                'message' => "Le montant minimum de commande est de {$restaurant->minimum_order_amount} FCFA",
            ], 422);
        }

        // Calculer les frais de livraison
        $deliveryFee = 0;
        $deliveryDistance = 0;
        if ($request->type === 'delivery') {
            $address = $user->addresses()->findOrFail($request->address_id);
            $deliveryService = new DeliveryService();
            $deliveryInfo = $deliveryService->calculateDeliveryFee($restaurant, $address);
            
            if (!$deliveryInfo['in_range']) {
                return response()->json([
                    'success' => false,
                    'message' => "Votre adresse est hors de la zone de livraison (rayon: {$restaurant->delivery_radius_km} km)",
                ], 422);
            }
            
            $deliveryFee = $deliveryInfo['fee'];
            $deliveryDistance = $deliveryInfo['distance'];
        }

        // Calculer la réduction
        $discountAmount = 0;
        $promoCode = null;
        if ($request->promo_code) {
            $promoCode = \App\Models\PromoCode::where('code', $request->promo_code)->first();
            
            if (!$promoCode) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le code promo n\'existe pas.',
                ], 422);
            }
            
            if (!$promoCode->isValidForUser($user->id, $subtotal)) {
                // Messages d'erreur plus détaillés
                $errorMessage = 'Le code promo n\'est pas valide.';
                
                if (!$promoCode->is_active) {
                    $errorMessage = 'Ce code promo est désactivé.';
                } elseif ($promoCode->starts_at && now() < $promoCode->starts_at) {
                    $errorMessage = 'Ce code promo n\'est pas encore actif.';
                } elseif ($promoCode->expires_at && now() > $promoCode->expires_at) {
                    $errorMessage = 'Ce code promo a expiré.';
                } elseif ($subtotal < $promoCode->minimum_order_amount) {
                    $errorMessage = "Le montant minimum de commande pour ce code est de {$promoCode->minimum_order_amount} FCFA.";
                } elseif ($promoCode->max_uses && $promoCode->usages()->count() >= $promoCode->max_uses) {
                    $errorMessage = 'Ce code promo a atteint sa limite d\'utilisations.';
                } elseif ($promoCode->usages()->where('user_id', $user->id)->count() >= $promoCode->max_uses_per_user) {
                    $errorMessage = 'Vous avez déjà utilisé ce code promo le nombre maximum de fois autorisé.';
                }
                
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                ], 422);
            }
            
            $discountAmount = $promoCode->calculateDiscount($subtotal);
        }

        $total = $subtotal + $deliveryFee - $discountAmount;

        DB::beginTransaction();
        try {
            // Créer la commande
            $order = Order::create([
                'user_id' => $user->id,
                'restaurant_id' => $restaurant->id,
                'address_id' => $request->type === 'delivery' ? $request->address_id : null,
                'type' => $request->type,
                'status' => 'pending',
                'subtotal' => $subtotal,
                'delivery_fee' => $deliveryFee,
                'discount_amount' => $discountAmount,
                'total' => $total,
                'promo_code_id' => $promoCode?->id,
                'scheduled_at' => $request->scheduled_at,
                'special_instructions' => $request->special_instructions,
            ]);

            // Créer les articles de commande
            foreach ($cart->items as $cartItem) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'dish_id' => $cartItem->dish_id,
                    'dish_name' => $cartItem->dish->name,
                    'quantity' => $cartItem->quantity,
                    'unit_price' => $cartItem->unit_price,
                    'total_price' => $cartItem->total_price,
                    'selected_options' => $cartItem->selected_options,
                    'special_instructions' => $cartItem->special_instructions,
                ]);

                // Mettre à jour le compteur de commandes du plat
                $cartItem->dish->increment('order_count');
            }

            // Créer le paiement
            $payment = \App\Models\Payment::create([
                'order_id' => $order->id,
                'method' => $request->payment_method,
                'status' => 'pending',
                'amount' => $total,
            ]);

            // Traiter le paiement selon la méthode
// Traiter le paiement selon la méthode
$paymentService = new PaymentService();
$paymentResult = null;

if ($request->payment_method === 'card') {
    // Paiement par carte via Stripe
    $paymentResult = $paymentService->createStripePayment($order);
    
    if (!$paymentResult['success']) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la création du paiement',
            'error' => $paymentResult['error'] ?? 'Erreur inconnue',
        ], 500);
    }
} elseif ($request->payment_method === 'mobile_money') {
    // Paiement Mobile Money (simulé)
    $paymentResult = $paymentService->processMobileMoneyPayment(
        $order,
        $request->mobile_money_provider ?? 'MTN',
        $request->mobile_money_number ?? ''
    );
    
    if (!$paymentResult['success']) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de l\'initialisation du paiement Mobile Money',
            'error' => $paymentResult['error'] ?? 'Erreur inconnue',
        ], 500);
    }
} else {
    // Paiement en espèces
    $paymentResult = $paymentService->processCashPayment($order);
    
    if (!$paymentResult['success']) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'Erreur lors du traitement du paiement en espèces',
        ], 500);
    }
}

            // Enregistrer l'utilisation du code promo
            // Re-vérifier que le code est toujours valide avant d'enregistrer l'utilisation
            // (pour éviter les cas où le code expire ou atteint max_uses entre la validation et l'enregistrement)
            if ($promoCode) {
                // Recharger le code promo pour avoir les données à jour
                $promoCode->refresh();
                
                // Re-valider une dernière fois avant d'enregistrer
                if (!$promoCode->isValidForUser($user->id, $subtotal)) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Le code promo n\'est plus valide. Il a peut-être expiré ou atteint sa limite d\'utilisation.',
                    ], 422);
                }
                
                // Enregistrer l'utilisation
                \App\Models\PromoCodeUsage::create([
                    'promo_code_id' => $promoCode->id,
                    'user_id' => $user->id,
                    'order_id' => $order->id,
                    'discount_amount' => $discountAmount,
                ]);
            }

            // Vider le panier
            $cart->items()->delete();

            DB::commit();

            // Envoyer une notification de confirmation de commande
            try {
                $notificationService = new NotificationService();
                $notificationService->sendOrderStatusUpdate($user, $order->fresh(), 'pending');
            } catch (\Exception $e) {
                // Ne pas faire échouer la commande si la notification échoue
                Log::error('Erreur lors de l\'envoi de notification de commande', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $responseData = [
                'order' => new OrderResource($order->load(['restaurant', 'address', 'items.dish', 'payment', 'promoCode'])),
            ];
            // AJOUTER : Informations de paiement Stripe
            if ($request->payment_method === 'card' && $paymentResult && isset($paymentResult['client_secret'])) {
                $responseData['payment'] = [
                    'client_secret' => $paymentResult['client_secret'],
                    'payment_intent_id' => $paymentResult['payment_intent_id'],
                    'publishable_key' => $paymentResult['publishable_key'],
                ];
            }

            // AJOUTER : Informations de paiement Mobile Money
            if ($request->payment_method === 'mobile_money' && $paymentResult && isset($paymentResult['payment_url'])) {
                $responseData['payment'] = [
                    'transaction_id' => $paymentResult['transaction_id'],
                    'payment_url' => $paymentResult['payment_url'],
                    'token' => $paymentResult['token'],
                ];
            }


            return response()->json([
                'success' => true,
                'message' => 'Commande créée avec succès',
                'data' => $responseData,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la commande',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/orders/{id}",
     *     summary="Détails d'une commande",
     *     tags={"Commandes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la commande",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Détails de la commande",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object", @OA\Property(property="order", type="object"))
     *         )
     *     )
     * )
     */
    public function show(Request $request, $id)
    {
        $order = $request->user()->orders()
            ->with(['restaurant', 'address', 'items.dish', 'payment', 'promoCode'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'order' => new OrderResource($order),
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/orders/{id}/cancel",
     *     summary="Annuler une commande",
     *     tags={"Commandes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la commande",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"reason"},
     *             @OA\Property(property="reason", type="string", example="Changement d'avis")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Commande annulée",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Commande annulée"),
     *             @OA\Property(property="data", type="object", @OA\Property(property="order", type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Commande ne peut plus être annulée"
     *     )
     * )
     */
    public function cancel(Request $request, $id)
    {
        $order = $request->user()->orders()->findOrFail($id);

        if (!in_array($order->status, ['pending', 'confirmed'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cette commande ne peut plus être annulée',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        $order->update([
            'status' => 'cancelled',
            'cancellation_reason' => $request->reason,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Commande annulée',
            'data' => [
                'order' => $order->fresh(),
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/orders/{id}/invoice/download",
     *     summary="Télécharger la facture PDF",
     *     tags={"Commandes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la commande",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Fichier PDF de la facture",
     *         @OA\MediaType(
     *             mediaType="application/pdf"
     *         )
     *     )
     * )
     */
    public function downloadInvoice(Request $request, $id)
    {
        $order = $request->user()->orders()->findOrFail($id);
        
        $invoiceService = new InvoiceService();
        return $invoiceService->downloadInvoice($order);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/orders/{id}/invoice",
     *     summary="Obtenir la facture en base64",
     *     tags={"Commandes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la commande",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Facture en base64",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="invoice_base64", type="string", example="JVBERi0xLjQKJeLjz9MK..."),
     *                 @OA\Property(property="filename", type="string", example="facture_ORD-2024-001.pdf")
     *             )
     *         )
     *     )
     * )
     */
    public function getInvoice(Request $request, $id)
    {
        $order = $request->user()->orders()->findOrFail($id);
        
        $invoiceService = new InvoiceService();
        $invoiceBase64 = $invoiceService->getInvoiceBase64($order);

        return response()->json([
            'success' => true,
            'data' => [
                'invoice_base64' => $invoiceBase64,
                'filename' => 'facture_' . $order->order_number . '.pdf',
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/orders/{id}/reorder",
     *     summary="Recommander une commande (ajouter au panier)",
     *     tags={"Commandes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la commande à recommander",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Commande ajoutée au panier",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Commande ajoutée au panier"),
     *             @OA\Property(property="data", type="object", @OA\Property(property="cart", type="object"))
     *         )
     *     )
     * )
     */
    public function reorder(Request $request, $id)
    {
        $originalOrder = $request->user()->orders()
            ->with('items.dish')
            ->findOrFail($id);

        $user = $request->user();
        $cart = Cart::firstOrCreate(['user_id' => $user->id]);

        // Vider le panier actuel
        $cart->items()->delete();

        // Ajouter les plats de la commande précédente au panier
        foreach ($originalOrder->items as $orderItem) {
            $dish = \App\Models\Dish::find($orderItem->dish_id);
            
            if ($dish && $dish->is_available) {
                \App\Models\CartItem::create([
                    'cart_id' => $cart->id,
                    'dish_id' => $dish->id,
                    'quantity' => $orderItem->quantity,
                    'unit_price' => $dish->final_price,
                    'selected_options' => $orderItem->selected_options,
                    'special_instructions' => $orderItem->special_instructions,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Commande ajoutée au panier',
            'data' => [
                'cart' => $cart->load('items.dish'),
            ]
        ]);
    }
}
