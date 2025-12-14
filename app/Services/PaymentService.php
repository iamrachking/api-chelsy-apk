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
                // ❌ Le paiement n'a pas de méthode de paiement
                return [
                    'success' => false,
                    'error' => 'requires_payment_method',
                    'message' => 'La méthode de paiement est manquante ou invalide',
                ];

            } elseif ($paymentIntent->status === 'requires_action') {
                // ❌ Action requise (3D Secure, etc.)
                return [
                    'success' => false,
                    'error' => 'requires_action',
                    'message' => 'Action supplémentaire requise',
                ];

            } elseif ($paymentIntent->status === 'processing') {
                // ⏳ En cours de traitement
                return [
                    'success' => false,
                    'error' => 'processing',
                    'message' => 'Paiement en cours de traitement',
                ];

            } else {
                // ❌ Statut non géré
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
        try {
            // TODO: Implémenter l'intégration FedaPay
            // Pour l'instant, retourner une erreur
            
            return [
                'success' => false,
                'error' => 'Mobile Money non encore implémenté',
            ];

        } catch (\Exception $e) {
            Log::error('Mobile Money payment creation error', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}