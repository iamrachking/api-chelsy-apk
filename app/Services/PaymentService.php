<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Exception\ApiErrorException;

class PaymentService
{
    public function __construct()
    {
        // Configuration Stripe
        $stripeSecret = config('services.stripe.secret');
        if ($stripeSecret) {
            Stripe::setApiKey($stripeSecret);
        }
    }

    /**
     * Créer un paiement Stripe
     */
    public function createStripePayment(Order $order): array
    {
        try {
            $payment = $order->payment;
            
            if (!$payment) {
                throw new \Exception('Aucun paiement associé à cette commande');
            }

            // Vérifier si Stripe est configuré
            if (!config('services.stripe.secret')) {
                Log::warning('Stripe non configuré - simulation du paiement');
                // Simuler un paiement réussi pour le développement
                $payment->update([
                    'status' => 'completed',
                    'transaction_id' => 'sim_' . uniqid(),
                    'payment_data' => [
                        'simulated' => true,
                        'created_at' => now()->toDateTimeString(),
                    ],
                ]);
                
                $order->update(['status' => 'confirmed']);
                
                return [
                    'success' => true,
                    'payment_id' => $payment->id,
                    'simulated' => true,
                ];
            }

            $paymentIntent = PaymentIntent::create([
                'amount' => (int)($order->total * 100),
                'currency' => 'xof',
                'metadata' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'payment_id' => $payment->id,
                    'user_id' => $order->user_id,
                ],
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ]);

            $payment->update([
                'transaction_id' => $paymentIntent->id,
                'payment_data' => [
                    'client_secret' => $paymentIntent->client_secret,
                    'status' => $paymentIntent->status,
                    'created_at' => now()->toDateTimeString(),
                ],
            ]);

            return [
                'success' => true,
                'payment_id' => $payment->id,
                'client_secret' => $paymentIntent->client_secret,
                'payment_intent_id' => $paymentIntent->id,
                'publishable_key' => config('services.stripe.key'),
            ];
        } catch (ApiErrorException $e) {
            Log::error('Stripe Payment Creation Error', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            
            if (isset($payment)) {
                $payment->markAsFailed($e->getMessage());
            }
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        } catch (\Exception $e) {
            Log::error('Payment Creation Error', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Confirmer un paiement Stripe
     */
    public function confirmStripePayment(string $paymentIntentId, Order $order): array
    {
        try {
            $paymentIntent = PaymentIntent::retrieve($paymentIntentId);

            if ($paymentIntent->status === 'succeeded') {
                $payment = $order->payment;
                if ($payment) {
                    $payment->update([
                        'status' => 'completed',
                        'payment_data' => array_merge(
                            $payment->payment_data ?? [],
                            [
                                'confirmed_at' => now()->toDateTimeString(),
                                'payment_method' => $paymentIntent->payment_method ?? null,
                            ]
                        ),
                    ]);

                    $order->update(['status' => 'confirmed']);
                }

                return [
                    'success' => true,
                    'message' => 'Paiement confirmé avec succès',
                ];
            }

            return [
                'success' => false,
                'message' => 'Le paiement n\'a pas encore été confirmé. Statut: ' . $paymentIntent->status,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Stripe Confirmation Error', [
                'payment_intent_id' => $paymentIntentId,
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Traiter un paiement Mobile Money
     * TEMPORAIRE: Simulé car FedaPay n'est pas installé
     */
    public function processMobileMoneyPayment(Order $order, string $provider, string $phoneNumber): array
    {
        try {
            $payment = $order->payment;
            
            if (!$payment) {
                throw new \Exception('Aucun paiement associé à cette commande');
            }

            Log::warning('FedaPay non installé - simulation du paiement Mobile Money');

            // Simuler un paiement Mobile Money réussi
            $payment->update([
                'mobile_money_provider' => $provider,
                'mobile_money_number' => $phoneNumber,
                'transaction_id' => 'mm_sim_' . uniqid(),
                'status' => 'completed', // Directement completed pour la simulation
                'payment_data' => [
                    'simulated' => true,
                    'provider' => $provider,
                    'phone' => $phoneNumber,
                    'initiated_at' => now()->toDateTimeString(),
                ],
            ]);

            // Confirmer la commande
            $order->update(['status' => 'confirmed']);

            return [
                'success' => true,
                'message' => 'Paiement Mobile Money simulé avec succès',
                'transaction_id' => $payment->transaction_id,
                'simulated' => true,
            ];
        } catch (\Exception $e) {
            Log::error('Mobile Money Error', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            if (isset($payment)) {
                $payment->markAsFailed($e->getMessage());
            }

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Traiter un paiement en espèces
     */
    public function processCashPayment(Order $order): array
    {
        $payment = $order->payment;
        
        if ($payment) {
            $payment->update([
                'status' => 'pending',
                'transaction_id' => 'cash_' . uniqid(),
                'payment_data' => [
                    'type' => 'cash_on_delivery',
                    'initiated_at' => now()->toDateTimeString(),
                ],
            ]);
            
            // Confirmer la commande directement pour espèces
            $order->update(['status' => 'confirmed']);
        }

        return [
            'success' => true,
            'message' => 'Paiement en espèces enregistré. À payer à la livraison.',
        ];
    }

    /**
     * Vérifier le statut d'une transaction (simulé)
     */
    public function checkFedaPayStatus(string $transactionId): array
    {
        return [
            'success' => true,
            'status' => 'approved',
        ];
    }
}