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
        $stripeSecret = config('services.stripe.secret');
        if ($stripeSecret) {
            Stripe::setApiKey($stripeSecret);
        }
    }

    /**
     * Créer un paiement Stripe
     * 
     * @param Order $order
     * @return array
     */
    public function createStripePayment(Order $order): array
    {
        try {
            $paymentIntent = PaymentIntent::create([
                'amount' => (int)($order->total * 100), // Convertir en centimes
                'currency' => 'xof', // Franc CFA
                'metadata' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                ],
            ]);

            // Mettre à jour le paiement
            $payment = $order->payment;
            if ($payment) {
                $payment->update([
                    'transaction_id' => $paymentIntent->id,
                    'payment_data' => [
                        'client_secret' => $paymentIntent->client_secret,
                        'status' => $paymentIntent->status,
                    ],
                ]);
            }

            return [
                'success' => true,
                'client_secret' => $paymentIntent->client_secret,
                'payment_intent_id' => $paymentIntent->id,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Stripe Payment Error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Confirmer un paiement Stripe
     * 
     * @param string $paymentIntentId
     * @param Order $order
     * @return array
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
                            ['confirmed_at' => now()->toDateTimeString()]
                        ),
                    ]);
                }

                return [
                    'success' => true,
                    'message' => 'Paiement confirmé avec succès',
                ];
            }

            return [
                'success' => false,
                'message' => 'Le paiement n\'a pas été confirmé',
            ];
        } catch (ApiErrorException $e) {
            Log::error('Stripe Confirmation Error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Traiter un paiement Mobile Money
     * 
     * @param Order $order
     * @param string $provider (MTN, Moov)
     * @param string $phoneNumber
     * @return array
     */
    public function processMobileMoneyPayment(Order $order, string $provider, string $phoneNumber): array
    {
        // TODO: Implémenter l'intégration avec les APIs Mobile Money
        // Pour l'instant, on simule le processus
        
        $payment = $order->payment;
        if ($payment) {
            $payment->update([
                'method' => 'mobile_money',
                'mobile_money_provider' => $provider,
                'mobile_money_number' => $phoneNumber,
                'transaction_id' => 'MM_' . time() . '_' . $order->id,
                'status' => 'pending',
                'payment_data' => [
                    'provider' => $provider,
                    'phone' => $phoneNumber,
                    'initiated_at' => now()->toDateTimeString(),
                ],
            ]);
        }

        // Dans un vrai système, on appellerait l'API du fournisseur Mobile Money ici
        // Pour l'instant, on retourne un statut en attente

        return [
            'success' => true,
            'message' => 'Paiement Mobile Money initié. En attente de confirmation.',
            'transaction_id' => $payment->transaction_id ?? null,
        ];
    }

    /**
     * Traiter un paiement en espèces
     * 
     * @param Order $order
     * @return array
     */
    public function processCashPayment(Order $order): array
    {
        $payment = $order->payment;
        if ($payment) {
            $payment->update([
                'method' => 'cash',
                'status' => 'pending',
            ]);
        }

        return [
            'success' => true,
            'message' => 'Paiement en espèces enregistré. À payer à la livraison.',
        ];
    }
}

