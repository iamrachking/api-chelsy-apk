<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Services\PaymentService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

/**
 * @OA\Tag(
 *     name="Paiements",
 *     description="Endpoints pour la gestion des paiements"
 * )
 */
class PaymentController extends Controller
{
    protected PaymentService $paymentService;
    protected NotificationService $notificationService;

    public function __construct(PaymentService $paymentService, NotificationService $notificationService)
    {
        $this->paymentService = $paymentService;
        $this->notificationService = $notificationService;
    }

    // ======================= STRIPE PAYMENT =======================

    /**
     * @OA\Post(
     *     path="/api/v1/payments/stripe/confirm",
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
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function confirmStripePayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|integer|exists:orders,id',
            'payment_intent_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $orderId = (int) $request->order_id;
            $paymentIntentId = $request->payment_intent_id;

            // ✅ Vérifier que la commande appartient à l'utilisateur
            $order = $request->user()->orders()->findOrFail($orderId);

            if ($order->payment->method !== 'card') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette commande n\'utilise pas le paiement par carte',
                ], 422);
            }

            // ✅ CORRECTION: Les arguments dans le BON ORDRE
            // confirmStripePayment(int $orderId, string $paymentIntentId)
            $result = $this->paymentService->confirmStripePayment($orderId, $paymentIntentId);

            if ($result['success']) {
                try {
                    $this->notificationService->sendPaymentConfirmation($request->user(), $order);
                } catch (\Exception $e) {
                    Log::error('Erreur notification paiement', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                    ]);
                }

                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'data' => [
                        'order_id' => $order->id,
                        'status' => 'confirmed',
                    ]
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Erreur lors de la confirmation du paiement',
                'error' => $result['error'] ?? null,
            ], 422);

        } catch (\Exception $e) {
            Log::error('Erreur confirmation Stripe', [
                'order_id' => $request->order_id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la confirmation du paiement',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ======================= MOBILE MONEY PAYMENT (FedaPay) =======================

    /**
     * @OA\Post(
     *     path="/api/v1/payments/mobile-money/create",
     *     summary="Créer un paiement Mobile Money (FedaPay)",
     *     tags={"Paiements"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"order_id", "provider", "phone_number"},
     *             @OA\Property(property="order_id", type="integer", example=1),
     *             @OA\Property(property="provider", type="string", enum={"MTN", "Moov"}, example="MTN"),
     *             @OA\Property(property="phone_number", type="string", example="+229 12 34 56 78")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transaction Mobile Money créée",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function createMobileMoneyPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'provider' => 'required|in:MTN,Moov',
            'phone_number' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        $order = $request->user()->orders()->findOrFail($request->order_id);

        if ($order->payment->method !== 'mobile_money') {
            return response()->json([
                'success' => false,
                'message' => 'Cette commande n\'utilise pas Mobile Money',
            ], 422);
        }

        $result = $this->paymentService->createMobileMoneyPayment(
            $order,
            $request->provider,
            $request->phone_number
        );

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => [
                    'transaction_id' => $result['transaction_id'],
                    'status' => $result['status'],
                    'amount' => $result['amount'],
                    'provider' => $result['provider'],
                ]
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la création de la transaction',
            'error' => $result['error'] ?? null,
        ], 500);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/payments/mobile-money/status",
     *     summary="Vérifier le statut d'un paiement Mobile Money",
     *     tags={"Paiements"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"order_id"},
     *             @OA\Property(property="order_id", type="integer", example=1)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Statut du paiement",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function checkMobileMoneyStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        $order = $request->user()->orders()->findOrFail($request->order_id);
        $payment = $order->payment;

        if ($payment->method !== 'mobile_money') {
            return response()->json([
                'success' => false,
                'message' => 'Cette commande n\'utilise pas Mobile Money',
            ], 422);
        }

        $transactionId = $payment->transaction_id;
        if (!$transactionId) {
            return response()->json([
                'success' => false,
                'message' => 'Aucune transaction trouvée',
            ], 422);
        }

        $result = $this->paymentService->checkMobileMoneyStatus($transactionId);

        if ($result['success']) {
            $status = $result['status'];
            
            // Mettre à jour le statut si approuvé
            if ($status === 'approved' && $payment->status !== 'completed') {
                $payment->markAsCompleted();
                $order->update(['status' => 'confirmed']);
                
                try {
                    $this->notificationService->sendPaymentConfirmation($request->user(), $order);
                } catch (\Exception $e) {
                    Log::error('Erreur notification paiement', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            } 
            // Mettre à jour si refusé
            elseif ($status === 'declined' && $payment->status !== 'failed') {
                $payment->markAsFailed('Paiement refusé par le fournisseur Mobile Money');
            }
            // Mettre à jour si annulé
            elseif ($status === 'canceled' && $payment->status !== 'failed') {
                $payment->markAsFailed('Paiement annulé par l\'utilisateur');
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'status' => $status,
                    'payment_status' => $payment->status,
                    'order_status' => $order->status,
                    'amount' => $payment->amount,
                ]
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la vérification du statut',
            'error' => $result['error'] ?? null,
        ], 500);
    }

    // ======================= WEBHOOKS =======================

    /**
     * @OA\Post(
     *     path="/api/v1/webhooks/stripe",
     *     summary="Webhook Stripe - Confirmation automatique des paiements"
     * )
     */
    public function stripeWebhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = config('services.stripe.webhook_secret');

        try {
            if ($webhookSecret) {
                $event = Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
            } else {
                $event = json_decode($payload);
            }

            $this->paymentService->handleStripeWebhookEvent($event);
        } catch (\UnexpectedValueException $e) {
            Log::error('Webhook Stripe: payload invalide', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (SignatureVerificationException $e) {
            Log::error('Webhook Stripe: signature invalide', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        return response()->json(['received' => true]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/webhooks/fedapay",
     *     summary="Webhook FedaPay - Confirmation automatique des paiements Mobile Money"
     * )
     */
    public function fedaPayWebhook(Request $request)
    {
        $payload = $request->all();

        Log::info('Webhook FedaPay reçu', ['payload' => $payload]);

        try {
            // Vérifier la signature FedaPay (optionnel mais recommandé)
            // À implémenter selon la doc FedaPay

            $this->paymentService->handleFedaPayWebhookEvent($payload);
        } catch (\Exception $e) {
            Log::error('Erreur traitement webhook FedaPay', [
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Processing error'], 500);
        }

        return response()->json(['received' => true]);
    }
}