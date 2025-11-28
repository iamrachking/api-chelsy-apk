<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Paiements",
 *     description="Endpoints pour la gestion des paiements"
 * )
 */
class PaymentController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/v1/payments/confirm-stripe",
     *     summary="Confirmer un paiement Stripe",
     *     tags={"Paiements"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"order_id", "payment_intent_id"},
     *             @OA\Property(property="order_id", type="integer", example=1),
     *             @OA\Property(property="payment_intent_id", type="string", example="pi_xxxxxxxxxxxxx")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Paiement confirmé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Paiement confirmé avec succès"),
     *             @OA\Property(property="data", type="object", @OA\Property(property="order", type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation ou paiement non confirmé"
     *     )
     * )
     */
    public function confirmStripePayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'payment_intent_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        $order = $request->user()->orders()->findOrFail($request->order_id);

        if ($order->payment->method !== 'card') {
            return response()->json([
                'success' => false,
                'message' => 'Cette commande n\'utilise pas le paiement par carte',
            ], 422);
        }

        $paymentService = new PaymentService();
        $result = $paymentService->confirmStripePayment($request->payment_intent_id, $order);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => [
                    'order' => $order->fresh()->load(['restaurant', 'address', 'items.dish', 'payment']),
                ]
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'] ?? 'Erreur lors de la confirmation du paiement',
            'error' => $result['error'] ?? null,
        ], 422);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/webhooks/stripe",
     *     summary="Webhook Stripe pour les notifications de paiement",
     *     tags={"Paiements"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="type", type="string", example="payment_intent.succeeded"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Webhook reçu",
     *         @OA\JsonContent(
     *             @OA\Property(property="received", type="boolean", example=true)
     *         )
     *     )
     * )
     */
    public function stripeWebhook(Request $request)
    {
        // TODO: Implémenter la vérification de la signature du webhook
        // Pour l'instant, c'est une structure de base
        
        $payload = $request->all();
        $eventType = $payload['type'] ?? null;

        if ($eventType === 'payment_intent.succeeded') {
            $paymentIntent = $payload['data']['object'];
            $orderId = $paymentIntent['metadata']['order_id'] ?? null;

            if ($orderId) {
                $order = Order::find($orderId);
                if ($order && $order->payment) {
                    $order->payment->update([
                        'status' => 'completed',
                    ]);
                }
            }
        }

        return response()->json(['received' => true]);
    }
}
