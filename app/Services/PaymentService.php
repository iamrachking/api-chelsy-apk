<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    // ✅ Créer un paiement Stripe (PaymentIntent)
    public function createStripePayment(Order $order): array
    {
        try {
            // Initialiser Stripe avec la clé secrète
            \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

            // Créer un PaymentIntent
            $paymentIntent = \Stripe\PaymentIntent::create([
                'amount' => (int) ($order->total * 100), // Convertir en centimes
                'currency' => strtolower(config('services.stripe.currency', 'xof')),
                'metadata' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'user_id' => $order->user_id,
                ],
                'description' => "Commande #{$order->order_number}",
            ]);

            Log::info('Stripe PaymentIntent created', [
                'order_id' => $order->id,
                'payment_intent_id' => $paymentIntent->id,
                'amount' => $order->total,
                'status' => $paymentIntent->status,
            ]);

            return [
                'success' => true,
                'client_secret' => $paymentIntent->client_secret,
                'payment_intent_id' => $paymentIntent->id,
                'publishable_key' => config('services.stripe.public'),
                'amount' => $order->total,
                'currency' => strtoupper(config('services.stripe.currency', 'XOF')),
            ];

        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('Stripe API Error', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];

        } catch (\Exception $e) {
            Log::error('Stripe Payment Creation Error', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    // ✅ Confirmer un paiement Stripe (appelé après que Stripe dise OK)
    public function confirmStripePayment(int $orderId, string $paymentIntentId): array
    {
        try {
            \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

            // Récupérer le PaymentIntent depuis Stripe
            $paymentIntent = \Stripe\PaymentIntent::retrieve($paymentIntentId);

            Log::info('Stripe PaymentIntent retrieved', [
                'order_id' => $orderId,
                'payment_intent_id' => $paymentIntentId,
                'status' => $paymentIntent->status,
            ]);

            // Vérifier le statut
            if ($paymentIntent->status === 'succeeded') {
                // ✅ Paiement réussi
                $order = Order::findOrFail($orderId);
                $order->update(['status' => 'confirmed']);

                // Mettre à jour le payment
                $payment = $order->payment;
                if ($payment) {
                    $payment->update([
                        'status' => 'completed',
                        'transaction_id' => $paymentIntentId,
                    ]);
                }

                Log::info('Stripe payment confirmed', [
                    'order_id' => $orderId,
                    'payment_intent_id' => $paymentIntentId,
                ]);

                return [
                    'success' => true,
                    'message' => 'Paiement confirmé avec succès',
                    'status' => 'completed',
                ];

            } elseif ($paymentIntent->status === 'requires_payment_method') {
                return [
                    'success' => false,
                    'error' => 'requires_payment_method',
                    'message' => 'La méthode de paiement est manquante ou invalide',
                ];

            } elseif ($paymentIntent->status === 'requires_action') {
                return [
                    'success' => false,
                    'error' => 'requires_action',
                    'message' => 'Action supplémentaire requise',
                ];

            } elseif ($paymentIntent->status === 'processing') {
                return [
                    'success' => false,
                    'error' => 'processing',
                    'message' => 'Paiement en cours de traitement',
                ];

            } else {
                return [
                    'success' => false,
                    'error' => $paymentIntent->status,
                    'message' => "Statut de paiement non attendu: {$paymentIntent->status}",
                ];
            }

        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('Stripe Confirmation Error', [
                'order_id' => $orderId,
                'payment_intent_id' => $paymentIntentId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];

        } catch (\Exception $e) {
            Log::error('Payment Confirmation Error', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    // ✅ Traiter un paiement en espèces
    public function processCashPayment(Order $order): array
    {
        try {
            // Mettre à jour le paiement
            $payment = $order->payment;
            if ($payment) {
                $payment->update([
                    'status' => 'pending', // En attente de confirmation en magasin
                ]);
            }

            Log::info('Cash payment processed', [
                'order_id' => $order->id,
            ]);

            return [
                'success' => true,
                'amount' => $order->total,
                'message' => 'Paiement en espèces en attente de confirmation du livreur',
            ];

        } catch (\Exception $e) {
            Log::error('Cash payment processing error', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    // ✅ Créer un paiement Mobile Money (FedaPay)
    public function createMobileMoneyPayment(Order $order, string $provider, string $phoneNumber): array
    {
        return [
            'success' => false,
            'error' => 'Mobile Money n\'est pas encore disponible. Veuillez utiliser le paiement par carte ou en espèces.',
        ];
        // try {
        //     // TODO: Implémenter l'intégration FedaPay
        //     // Pour l'instant, retourner un succès simulé
            
        //     Log::info('Mobile Money payment creation initiated', [
        //         'order_id' => $order->id,
        //         'provider' => $provider,
        //     ]);

        //     return [
        //         'success' => false,
        //         'error' => 'Mobile Money non encore implémenté',
        //     ];

        // } catch (\Exception $e) {
        //     Log::error('Mobile Money payment creation error', [
        //         'order_id' => $order->id,
        //         'error' => $e->getMessage(),
        //     ]);

        //     return [
        //         'success' => false,
        //         'error' => $e->getMessage(),
        //     ];
        // }
    }

    // ✅ Vérifier le statut d'un paiement Mobile Money
    public function checkMobileMoneyStatus(string $transactionId): array
    {
        try {
            // TODO: Implémenter la vérification FedaPay
            // Pour l'instant, retourner un état par défaut
            
            Log::info('Mobile Money status check', [
                'transaction_id' => $transactionId,
            ]);

            return [
                'success' => false,
                'error' => 'Mobile Money status check non encore implémenté',
                'status' => 'pending',
            ];

        } catch (\Exception $e) {
            Log::error('Mobile Money status check error', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    // ✅ Traiter les événements Stripe Webhook
    public function handleStripeWebhookEvent($event): void
    {
        try {
            Log::info('Processing Stripe webhook event', [
                'event_type' => $event->type ?? 'unknown',
                'event_id' => $event->id ?? 'unknown',
            ]);

            // Gérer différents types d'événements
            switch ($event->type ?? null) {
                case 'payment_intent.succeeded':
                    $this->handlePaymentIntentSucceeded($event->data->object);
                    break;

                case 'payment_intent.payment_failed':
                    $this->handlePaymentIntentFailed($event->data->object);
                    break;

                case 'charge.refunded':
                    $this->handleChargeRefunded($event->data->object);
                    break;

                default:
                    Log::info('Unhandled Stripe webhook event type', [
                        'type' => $event->type ?? 'unknown',
                    ]);
                    break;
            }
        } catch (\Exception $e) {
            Log::error('Error handling Stripe webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    // ✅ Gérer les paiements réussis
    private function handlePaymentIntentSucceeded($paymentIntent): void
    {
        try {
            $orderId = $paymentIntent->metadata->order_id ?? null;
            
            if (!$orderId) {
                Log::warning('Payment intent succeeded but no order_id in metadata', [
                    'payment_intent_id' => $paymentIntent->id,
                ]);
                return;
            }

            $order = Order::find($orderId);
            if (!$order) {
                Log::warning('Order not found for payment intent', [
                    'order_id' => $orderId,
                    'payment_intent_id' => $paymentIntent->id,
                ]);
                return;
            }

            // Mettre à jour la commande et le paiement
            $order->update(['status' => 'confirmed']);
            
            if ($order->payment) {
                $order->payment->update([
                    'status' => 'completed',
                    'transaction_id' => $paymentIntent->id,
                ]);
            }

            Log::info('Payment intent succeeded and order updated', [
                'order_id' => $orderId,
                'payment_intent_id' => $paymentIntent->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Error handling payment intent succeeded', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    // ✅ Gérer les paiements échoués
    private function handlePaymentIntentFailed($paymentIntent): void
    {
        try {
            $orderId = $paymentIntent->metadata->order_id ?? null;
            
            if (!$orderId) {
                return;
            }

            $order = Order::find($orderId);
            if (!$order) {
                return;
            }

            // Mettre à jour le paiement
            if ($order->payment) {
                $order->payment->update([
                    'status' => 'failed',
                    'failure_reason' => $paymentIntent->last_payment_error?->message ?? 'Unknown error',
                ]);
            }

            Log::info('Payment intent failed and order updated', [
                'order_id' => $orderId,
                'payment_intent_id' => $paymentIntent->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Error handling payment intent failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    // ✅ Gérer les remboursements
    private function handleChargeRefunded($charge): void
    {
        try {
            Log::info('Processing charge refunded event', [
                'charge_id' => $charge->id,
                'amount' => $charge->amount_refunded,
            ]);

            // TODO: Implémenter la logique de remboursement
        } catch (\Exception $e) {
            Log::error('Error handling charge refunded', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    // ✅ Traiter les événements FedaPay Webhook
    public function handleFedaPayWebhookEvent($payload): void
    {
        try {
            Log::info('Processing FedaPay webhook event', [
                'transaction_id' => $payload['transaction_id'] ?? 'unknown',
                'status' => $payload['status'] ?? 'unknown',
            ]);

            // TODO: Implémenter la gestion des webhooks FedaPay
            
            Log::info('FedaPay webhook event processed');
        } catch (\Exception $e) {
            Log::error('Error handling FedaPay webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}