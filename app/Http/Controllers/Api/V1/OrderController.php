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
    protected PaymentService $paymentService;
    protected NotificationService $notificationService;

    public function __construct(PaymentService $paymentService, NotificationService $notificationService)
    {
        $this->paymentService = $paymentService;
        $this->notificationService = $notificationService;
    }

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
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        
        $orders = $request->user()->orders()
            ->with(['restaurant', 'address', 'items.dish', 'payment'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'orders' => OrderResource::collection($orders),
                'pagination' => [
                    'current_page' => $orders->currentPage(),
                    'last_page' => $orders->lastPage(),
                    'total' => $orders->total(),
                    'per_page' => $orders->perPage(),
                ],
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
     *             @OA\Property(property="type", type="string", enum={"delivery", "pickup"}, example="delivery"),
     *             @OA\Property(property="address_id", type="integer", nullable=true, example=1),
     *             @OA\Property(property="payment_method", type="string", enum={"card", "cash", "mobile_money"}, example="cash"),
     *             @OA\Property(property="mobile_money_provider", type="string", nullable=true, enum={"MTN", "Moov"}),
     *             @OA\Property(property="mobile_money_number", type="string", nullable=true, example="+229 12 34 56 78"),
     *             @OA\Property(property="promo_code", type="string", nullable=true),
     *             @OA\Property(property="scheduled_at", type="string", format="datetime", nullable=true),
     *             @OA\Property(property="special_instructions", type="string", nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Commande créée")
     * )
     */
    public function store(CreateOrderRequest $request)
    {
        $user = $request->user();
        $cart = Cart::with('items.dish')->where('user_id', $user->id)->first();

        // ==================== VALIDATIONS ====================
        if (!$cart || $cart->items->isEmpty()) {
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

        // Vérifier l'adresse pour la livraison
        $deliveryFee = 0;
        if ($request->type === 'delivery') {
            if (!$request->address_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Une adresse de livraison est requise',
                ], 422);
            }

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
        }

        // Vérifier et appliquer le code promo
        $discountAmount = 0;
        $promoCode = null;
        if ($request->promo_code) {
            $promoCode = \App\Models\PromoCode::where('code', $request->promo_code)->first();
            
            if (!$promoCode) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le code promo n\'existe pas',
                ], 422);
            }
            
            if (!$promoCode->isValidForUser($user->id, $subtotal)) {
                $errorMessage = $this->getPromoErrorMessage($promoCode, $subtotal, $user->id);
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
            // ==================== CRÉER LA COMMANDE ====================
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

            // ==================== CRÉER LES ITEMS ====================
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

                $cartItem->dish->increment('order_count');
            }

            // ==================== CRÉER LE PAIEMENT ====================
            $payment = \App\Models\Payment::create([
                'order_id' => $order->id,
                'method' => $request->payment_method,
                'status' => 'pending',
                'amount' => $total,
            ]);

            // ==================== TRAITER LE PAIEMENT ====================
            $paymentData = $this->processPayment($order, $request->payment_method, $request);

            if (!$paymentData['success']) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => $paymentData['message'],
                    'error' => $paymentData['error'] ?? null,
                ], 500);
            }

            // ==================== ENREGISTRER LE CODE PROMO ====================
            if ($promoCode) {
                $promoCode->refresh();
                
                if (!$promoCode->isValidForUser($user->id, $subtotal)) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Le code promo n\'est plus valide',
                    ], 422);
                }
                
                \App\Models\PromoCodeUsage::create([
                    'promo_code_id' => $promoCode->id,
                    'user_id' => $user->id,
                    'order_id' => $order->id,
                    'discount_amount' => $discountAmount,
                ]);
            }

            // ==================== VIDER LE PANIER ====================
            $cart->items()->delete();

            DB::commit();

            // ==================== ENVOYER NOTIFICATIONS ====================
            try {
                // Notification de création de commande
                $this->notificationService->sendOrderCreated($user, $order->fresh());
                
                // Si paiement par carte, envoyer aussi notification de paiement
                if ($order->payment && $order->payment->method === 'card') {
                    $this->notificationService->sendPaymentConfirmation($user, $order->fresh());
                }
            } catch (\Exception $e) {
                Log::error('Erreur envoi notifications commande', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // ==================== CONSTRUIRE LA RÉPONSE ====================
            $responseData = [
                'order' => new OrderResource($order->load(['restaurant', 'address', 'items.dish', 'payment', 'promoCode'])),
                'payment' => $paymentData['response'] ?? null,
            ];

            return response()->json([
                'success' => true,
                'message' => 'Commande créée avec succès',
                'data' => $responseData,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur création commande', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la commande',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Traiter le paiement selon la méthode
     */
    private function processPayment(Order $order, string $method, Request $request): array
    {
        if ($method === 'card') {
            // Paiement par CARTE via Stripe
            $result = $this->paymentService->createStripePayment($order);
            
            if (!$result['success']) {
                return [
                    'success' => false,
                    'message' => 'Erreur lors de la création du paiement Stripe',
                    'error' => $result['error'] ?? null,
                ];
            }

            return [
                'success' => true,
                'response' => [
                    'type' => 'stripe',
                    'client_secret' => $result['client_secret'],
                    'payment_intent_id' => $result['payment_intent_id'],
                    'publishable_key' => $result['publishable_key'],
                    'amount' => $result['amount'],
                    'currency' => $result['currency'],
                    'status' => 'pending',
                    'message' => 'Veuillez compléter le paiement avec votre carte bancaire',
                    'next_action' => [
                        'endpoint' => '/api/v1/payments/stripe/confirm',
                        'method' => 'POST',
                        'body' => [
                            'order_id' => $order->id,
                            'payment_intent_id' => $result['payment_intent_id'],
                        ]
                    ]
                ]
            ];

        } elseif ($method === 'mobile_money') {
            // Paiement MOBILE MONEY via FedaPay
            $result = $this->paymentService->createMobileMoneyPayment(
                $order,
                $request->mobile_money_provider ?? 'MTN',
                $request->mobile_money_number ?? ''
            );

            if (!$result['success']) {
                return [
                    'success' => false,
                    'message' => 'Erreur lors de la création du paiement Mobile Money',
                    'error' => $result['error'] ?? null,
                ];
            }

            return [
                'success' => true,
                'response' => [
                    'type' => 'mobile_money',
                    'transaction_id' => $result['transaction_id'],
                    'status' => $result['status'],
                    'amount' => $result['amount'],
                    'provider' => $result['provider'],
                    'message' => $result['message'],
                    'next_action' => [
                        'endpoint' => '/api/v1/payments/mobile-money/status',
                        'method' => 'POST',
                        'body' => [
                            'order_id' => $order->id,
                        ],
                        'polling' => [
                            'enabled' => true,
                            'interval_seconds' => 5,
                            'max_attempts' => 120,
                        ]
                    ]
                ]
            ];

        } else {
            // Paiement en ESPÈCES
            $result = $this->paymentService->processCashPayment($order);

            if (!$result['success']) {
                return [
                    'success' => false,
                    'message' => 'Erreur lors du traitement du paiement en espèces',
                ];
            }

            return [
                'success' => true,
                'response' => [
                    'type' => 'cash',
                    'status' => 'confirmed',
                    'amount' => $result['amount'],
                    'message' => $result['message'],
                ]
            ];
        }
    }

    /**
     * Obtenir le message d'erreur pour un code promo invalide
     */
    private function getPromoErrorMessage(\App\Models\PromoCode $promoCode, float $subtotal, int $userId): string
    {
        if (!$promoCode->is_active) {
            return 'Ce code promo est désactivé';
        }
        
        if ($promoCode->starts_at && now() < $promoCode->starts_at) {
            return 'Ce code promo n\'est pas encore actif';
        }
        
        if ($promoCode->expires_at && now() > $promoCode->expires_at) {
            return 'Ce code promo a expiré';
        }
        
        if ($subtotal < $promoCode->minimum_order_amount) {
            return "Le montant minimum de commande est de {$promoCode->minimum_order_amount} FCFA";
        }
        
        if ($promoCode->max_uses && $promoCode->usages()->count() >= $promoCode->max_uses) {
            return 'Ce code promo a atteint sa limite d\'utilisations';
        }
        
        if ($promoCode->usages()->where('user_id', $userId)->count() >= $promoCode->max_uses_per_user) {
            return 'Vous avez déjà utilisé ce code promo le maximum de fois autorisé';
        }

        return 'Ce code promo n\'est pas valide';
    }

    /**
     * @OA\Get(
     *     path="/api/v1/orders/{id}",
     *     summary="Détails d'une commande",
     *     tags={"Commandes"},
     *     security={{"bearerAuth":{}}}
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
     *     security={{"bearerAuth":{}}}
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
                'order' => new OrderResource($order->fresh()),
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/orders/{id}/invoice",
     *     summary="Obtenir la facture en base64",
     *     tags={"Commandes"},
     *     security={{"bearerAuth":{}}}
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
     * @OA\Get(
     *     path="/api/v1/orders/{id}/invoice/download",
     *     summary="Télécharger la facture PDF",
     *     tags={"Commandes"},
     *     security={{"bearerAuth":{}}}
     * )
     */
    public function downloadInvoice(Request $request, $id)
    {
        $order = $request->user()->orders()->findOrFail($id);
        $invoiceService = new InvoiceService();
        return $invoiceService->downloadInvoice($order);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/orders/{id}/reorder",
     *     summary="Recommander une commande",
     *     tags={"Commandes"},
     *     security={{"bearerAuth":{}}}
     * )
     */
    public function reorder(Request $request, $id)
    {
        $originalOrder = $request->user()->orders()
            ->with('items.dish')
            ->findOrFail($id);

        $user = $request->user();
        $cart = Cart::firstOrCreate(['user_id' => $user->id]);
        $cart->items()->delete();

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
            'data' => ['cart' => $cart->load('items.dish')]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/orders/{id}/status",
     *     summary="Mettre à jour le statut d'une commande",
     *     tags={"Commandes"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status"},
     *             @OA\Property(
     *                 property="status",
     *                 type="string",
     *                 enum={"pending", "confirmed", "preparing", "ready", "out_for_delivery", "delivered", "picked_up", "cancelled"},
     *                 example="confirmed"
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Statut mis à jour")
     * )
     */
    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,confirmed,preparing,ready,out_for_delivery,delivered,picked_up,cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $order = Order::findOrFail($id);
        $oldStatus = $order->status;
        $newStatus = $request->status;

        // Mettre à jour le statut
        $order->update(['status' => $newStatus]);

        // ✅ ENVOYER NOTIFICATION AU CLIENT
        try {
            $this->notificationService->sendOrderStatusUpdate(
                $order->user,
                $order->fresh(),
                $newStatus
            );
        } catch (\Exception $e) {
            Log::error('Erreur envoi notification statut', [
                'order_id' => $id,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => "Statut mis à jour: {$oldStatus} → {$newStatus}",
            'data' => [
                'order' => new OrderResource($order),
            ]
        ]);
    }
}