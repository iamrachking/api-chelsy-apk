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

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

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
     *             @OA\Property(property="data", type="object")
     *         )
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

        $result = $this->paymentService->confirmStripePayment($request->payment_intent_id, $order);

        if ($result['success']) {
            $order->refresh();
            
            // Envoyer une notification
            try {
                $notificationService = new NotificationService();
                $notificationService->sendPaymentConfirmation($request->user(), $order);
            } catch (\Exception $e) {
                Log::error('Notification Error', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => [
                    'order' => $order->load(['restaurant', 'address', 'items.dish', 'payment']),
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

        $result = $this->paymentService->checkFedaPayStatus($transactionId);

        if ($result['success']) {
            $status = $result['status'];
            
            // Mettre à jour le statut du paiement si nécessaire
            if ($status === 'approved' && $payment->status !== 'completed') {
                $payment->markAsCompleted();
                $order->update(['status' => 'confirmed']);
                
                // Envoyer notification
                try {
                    $notificationService = new NotificationService();
                    $notificationService->sendPaymentConfirmation($request->user(), $order);
                } catch (\Exception $e) {
                    Log::error('Notification Error', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            } elseif ($status === 'declined' && $payment->status !== 'failed') {
                $payment->markAsFailed('Paiement refusé par le fournisseur Mobile Money');
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'status' => $status,
                    'payment' => $payment->fresh(),
                    'order' => $order->fresh(),
                ]
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la vérification du statut',
            'error' => $result['error'] ?? null,
        ], 500);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/webhooks/stripe",
     *     summary="Webhook Stripe",
     *     tags={"Paiements"}
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
        } catch (\UnexpectedValueException $e) {
            Log::error('Stripe Webhook Error: Invalid payload', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (SignatureVerificationException $e) {
            Log::error('Stripe Webhook Error: Invalid signature', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        // Gérer les événements
        switch ($event->type) {
            case 'payment_intent.succeeded':
                $paymentIntent = $event->data->object;
                $this->handlePaymentIntentSucceeded($paymentIntent);
                break;

            case 'payment_intent.payment_failed':
                $paymentIntent = $event->data->object;
                $this->handlePaymentIntentFailed($paymentIntent);
                break;

            default:
                Log::info('Stripe Webhook: Unhandled event type', ['type' => $event->type]);
        }

        return response()->json(['received' => true]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/webhooks/fedapay",
     *     summary="Webhook FedaPay",
     *     tags={"Paiements"}
     * )
     */
    public function fedaPayWebhook(Request $request)
    {
        $payload = $request->all();

        Log::info('FedaPay Webhook Received', ['payload' => $payload]);

        // Vérifier la signature (optionnel mais recommandé)
        // $signature = $request->header('X-FedaPay-Signature');
        // Implémenter la vérification de signature selon la doc FedaPay

        $eventType = $payload['event'] ?? null;
        $transaction = $payload['transaction'] ?? null;

        if (!$eventType || !$transaction) {
            return response()->json(['error' => 'Invalid payload'], 400);
        }

        switch ($eventType) {
            case 'transaction.approved':
                $this->handleFedaPayApproved($transaction);
                break;

            case 'transaction.declined':
                $this->handleFedaPayDeclined($transaction);
                break;

            case 'transaction.canceled':
                $this->handleFedaPayCanceled($transaction);
                break;

            default:
                Log::info('FedaPay Webhook: Unhandled event', ['event' => $eventType]);
        }

        return response()->json(['received' => true]);
    }

    /**
     * Gérer un paiement Stripe réussi
     */
    private function handlePaymentIntentSucceeded($paymentIntent)
    {
        $paymentId = $paymentIntent->metadata->payment_id ?? null;
        $orderId = $paymentIntent->metadata->order_id ?? null;

        if ($orderId) {
            $order = Order::find($orderId);
            if ($order && $order->payment) {
                $order->payment->update([
                    'status' => 'completed',
                    'payment_data' => array_merge(
                        $order->payment->payment_data ?? [],
                        [
                            'webhook_received_at' => now()->toDateTimeString(),
                            'payment_method' => $paymentIntent->payment_method ?? null,
                        ]
                    ),
                ]);

                $order->update(['status' => 'confirmed']);

                Log::info('Stripe Payment Succeeded', [
                    'order_id' => $orderId,
                    'payment_intent' => $paymentIntent->id,
                ]);
            }
        }
    }

    /**
     * Gérer un paiement Stripe échoué
     */
    private function handlePaymentIntentFailed($paymentIntent)
    {
        $orderId = $paymentIntent->metadata->order_id ?? null;

        if ($orderId) {
            $order = Order::find($orderId);
            if ($order && $order->payment) {
                $order->payment->markAsFailed(
                    $paymentIntent->last_payment_error->message ?? 'Paiement échoué'
                );

                Log::error('Stripe Payment Failed', [
                    'order_id' => $orderId,
                    'payment_intent' => $paymentIntent->id,
                    'error' => $paymentIntent->last_payment_error->message ?? 'Unknown',
                ]);
            }
        }
    }

    /**
     * Gérer un paiement FedaPay approuvé
     */
    private function handleFedaPayApproved($transaction)
    {
        $transactionId = $transaction['id'] ?? null;

        if ($transactionId) {
            $payment = Payment::where('transaction_id', $transactionId)->first();
            
            if ($payment) {
                $payment->markAsCompleted();
                $payment->order->update(['status' => 'confirmed']);

                // Envoyer notification
                try {
                    $notificationService = new NotificationService();
                    $notificationService->sendPaymentConfirmation(
                        $payment->order->user,
                        $payment->order
                    );
                } catch (\Exception $e) {
                    Log::error('Notification Error', [
                        'payment_id' => $payment->id,
                        'error' => $e->getMessage(),
                    ]);
                }

                Log::info('FedaPay Payment Approved', [
                    'transaction_id' => $transactionId,
                    'order_id' => $payment->order_id,
                ]);
            }
        }
    }

    /**
     * Gérer un paiement FedaPay refusé
     */
    private function handleFedaPayDeclined($transaction)
    {
        $transactionId = $transaction['id'] ?? null;

        if ($transactionId) {
            $payment = Payment::where('transaction_id', $transactionId)->first();
            
            if ($payment) {
                $payment->markAsFailed('Paiement refusé par le fournisseur Mobile Money');

                Log::error('FedaPay Payment Declined', [
                    'transaction_id' => $transactionId,
                    'order_id' => $payment->order_id,
                ]);
            }
        }
    }

    /**
     * Gérer un paiement FedaPay annulé
     */
    private function handleFedaPayCanceled($transaction)
    {
        $transactionId = $transaction['id'] ?? null;

        if ($transactionId) {
            $payment = Payment::where('transaction_id', $transactionId)->first();
            
            if ($payment) {
                $payment->markAsFailed('Paiement annulé par l\'utilisateur');

                Log::warning('FedaPay Payment Canceled', [
                    'transaction_id' => $transactionId,
                    'order_id' => $payment->order_id,
                ]);
            }
        }
    }
}