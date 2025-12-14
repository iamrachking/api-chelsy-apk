<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Exception\ApiErrorException;

// ✅ Vérifier si FedaPay est installé avant d'importer
if (class_exists('\FedaPay\FedaPay')) {
    use \FedaPay\FedaPay;
    use \FedaPay\Transaction;
    $FEDAPAY_AVAILABLE = true;
} else {
    $FEDAPAY_AVAILABLE = false;
}

class PaymentService
{
    public function __construct()
    {
        // Configuration Stripe
        $stripeSecret = config('services.stripe.secret');
        if ($stripeSecret) {
            Stripe::setApiKey($stripeSecret);
        }

        // Configuration FedaPay (si disponible)
        if ($FEDAPAY_AVAILABLE) {
            $fedapaySecret = config('services.fedapay.secret_key');
            if ($fedapaySecret) {
                FedaPay::setApiKey($fedapaySecret);
                FedaPay::setEnvironment(config('services.fedapay.environment', 'sandbox'));
            }
        }
    }

    // ======================== STRIPE PAYMENT ========================

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
                return $this->simulateStripePayment($payment, $order);
            }

            // Créer un PaymentIntent sur Stripe
            $paymentIntent = PaymentIntent::create([
                'amount' => (int)($order->total * 100),
                'currency' => 'xof',
                'metadata' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'payment_id' => $payment->id,
                    'user_id' => $order->user_id,
                    'restaurant_id' => $order->restaurant_id,
                ],
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
                'description' => "Commande {$order->order_number}",
                'receipt_email' => $order->user->email,
            ]);

            $payment->update([
                'transaction_id' => $paymentIntent->id,
                'status' => 'pending',
                'payment_data' => [
                    'payment_intent_id' => $paymentIntent->id,
                    'client_secret' => $paymentIntent->client_secret,
                    'status' => $paymentIntent->status,
                    'created_at' => now()->toDateTimeString(),
                ],
            ]);

            Log::info('Stripe PaymentIntent créé', [
                'order_id' => $order->id,
                'payment_intent' => $paymentIntent->id,
            ]);

            return [
                'success' => true,
                'payment_id' => $payment->id,
                'client_secret' => $paymentIntent->client_secret,
                'payment_intent_id' => $paymentIntent->id,
                'publishable_key' => config('services.stripe.key'),
                'amount' => $order->total,
                'currency' => 'XOF',
            ];
        } catch (ApiErrorException $e) {
            Log::error('Erreur création Stripe PaymentIntent', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'code' => $e->getHttpStatus(),
            ]);
            
            if (isset($payment)) {
                $payment->markAsFailed('Erreur Stripe: ' . $e->getMessage());
            }
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        } catch (\Exception $e) {
            Log::error('Erreur création paiement Stripe', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function confirmStripePayment(string $paymentIntentId, Order $order): array
    {
        try {
            if (!config('services.stripe.secret')) {
                Log::warning('Stripe non configuré - confirmation simulée');
                $payment = $order->payment;
                if ($payment && $payment->status === 'pending') {
                    $payment->update([
                        'status' => 'completed',
                        'payment_data' => array_merge(
                            $payment->payment_data ?? [],
                            ['confirmed_at' => now()->toDateTimeString()]
                        ),
                    ]);
                    $order->update(['status' => 'confirmed']);
                }
                return ['success' => true, 'message' => 'Paiement confirmé avec succès'];
            }

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
                                'charges' => $paymentIntent->charges ?? null,
                            ]
                        ),
                    ]);
                    $order->update(['status' => 'confirmed']);
                }

                Log::info('Paiement Stripe confirmé', [
                    'order_id' => $order->id,
                    'payment_intent' => $paymentIntentId,
                ]);

                return [
                    'success' => true,
                    'message' => 'Paiement confirmé avec succès',
                ];
            }

            return [
                'success' => false,
                'message' => 'Le paiement n\'a pas été confirmé. Statut: ' . $paymentIntent->status,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Erreur confirmation Stripe', [
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

    private function simulateStripePayment($payment, $order)
    {
        $paymentIntent = PaymentIntent::create([
            'amount' => (int)($order->total * 100),
            'currency' => 'xof',
            'metadata' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'payment_id' => $payment->id,
                'user_id' => $order->user_id,
            ],
        ]);

        $payment->update([
            'transaction_id' => $paymentIntent->id,
            'status' => 'pending',
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
            'simulated' => true,
        ];
    }

    // ======================== FEDAPAY PAYMENT ========================

    public function createMobileMoneyPayment(Order $order, string $provider, string $phoneNumber): array
    {
        try {
            $payment = $order->payment;
            
            if (!$payment) {
                throw new \Exception('Aucun paiement associé à cette commande');
            }

            // ✅ VÉRIFIER SI FEDAPAY EST DISPONIBLE
            if (!class_exists('\FedaPay\FedaPay')) {
                Log::warning('FedaPay non installé - simulation du paiement Mobile Money');
                return $this->simulateMobileMoneyPayment($payment, $order, $provider, $phoneNumber);
            }

            // Vérifier la configuration FedaPay
            if (!config('services.fedapay.secret_key')) {
                Log::warning('FedaPay non configuré - simulation du paiement Mobile Money');
                return $this->simulateMobileMoneyPayment($payment, $order, $provider, $phoneNumber);
            }

            // Normaliser le numéro de téléphone
            $phoneNumber = $this->normalizePhoneNumber($phoneNumber);

            // Créer une transaction FedaPay
            $transaction = Transaction::create([
                'description' => "Commande {$order->order_number}",
                'amount' => (float)$order->total,
                'currency' => [
                    'iso' => 'XOF'
                ],
                'customer' => [
                    'firstname' => $order->user->first_name,
                    'lastname' => $order->user->last_name,
                    'email' => $order->user->email,
                    'phone_number' => $phoneNumber,
                ],
                'metadata' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'payment_id' => $payment->id,
                    'provider' => $provider,
                ],
            ]);

            $payment->update([
                'transaction_id' => $transaction->id,
                'mobile_money_provider' => $provider,
                'mobile_money_number' => $phoneNumber,
                'status' => 'pending',
                'payment_data' => [
                    'fedapay_transaction_id' => $transaction->id,
                    'created_at' => now()->toDateTimeString(),
                    'phone' => $phoneNumber,
                    'provider' => $provider,
                ],
            ]);

            Log::info('Transaction FedaPay créée', [
                'order_id' => $order->id,
                'transaction_id' => $transaction->id,
                'provider' => $provider,
            ]);

            return [
                'success' => true,
                'payment_id' => $payment->id,
                'transaction_id' => $transaction->id,
                'status' => 'pending',
                'amount' => $order->total,
                'provider' => $provider,
                'message' => "Transaction Mobile Money initiée. Un SMS sera envoyé au {$phoneNumber}",
            ];
        } catch (\Exception $e) {
            Log::error('Erreur création transaction FedaPay', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            if (isset($payment)) {
                $payment->markAsFailed('Erreur FedaPay: ' . $e->getMessage());
            }

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function checkMobileMoneyStatus(string $transactionId): array
    {
        try {
            // ✅ VÉRIFIER SI FEDAPAY EST DISPONIBLE
            if (!class_exists('\FedaPay\FedaPay')) {
                Log::warning('FedaPay non installé - vérification simulée');
                return ['success' => true, 'status' => 'approved'];
            }

            if (!config('services.fedapay.secret_key')) {
                Log::warning('FedaPay non configuré - vérification simulée');
                return ['success' => true, 'status' => 'approved'];
            }

            $transaction = Transaction::retrieve($transactionId);
            $status = $this->mapFedaPayStatus($transaction->status);

            Log::info('Statut FedaPay vérifié', [
                'transaction_id' => $transactionId,
                'status' => $status,
            ]);

            return [
                'success' => true,
                'status' => $status,
                'transaction' => $transaction,
            ];
        } catch (\Exception $e) {
            Log::error('Erreur vérification statut FedaPay', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function simulateMobileMoneyPayment($payment, $order, $provider, $phoneNumber)
    {
        $payment->update([
            'mobile_money_provider' => $provider,
            'mobile_money_number' => $phoneNumber,
            'transaction_id' => 'mm_sim_' . uniqid(),
            'status' => 'pending',
            'payment_data' => [
                'simulated' => true,
                'provider' => $provider,
                'phone' => $phoneNumber,
                'initiated_at' => now()->toDateTimeString(),
            ],
        ]);

        return [
            'success' => true,
            'payment_id' => $payment->id,
            'transaction_id' => $payment->transaction_id,
            'status' => 'pending',
            'simulated' => true,
            'message' => 'Transaction Mobile Money simulée',
        ];
    }

    private function normalizePhoneNumber(string $phoneNumber): string
    {
        $cleaned = preg_replace('/[^\d+]/', '', $phoneNumber);
        
        if (strpos($cleaned, '+') === 0) {
            return $cleaned;
        }

        if (strpos($cleaned, '00') === 0) {
            return '+' . substr($cleaned, 2);
        }

        if (!strpos($cleaned, '+')) {
            return '+229' . $cleaned;
        }

        return $cleaned;
    }

    private function mapFedaPayStatus(string $fedaPayStatus): string
    {
        return match($fedaPayStatus) {
            'approved' => 'approved',
            'declined' => 'declined',
            'canceled' => 'canceled',
            'pending' => 'pending',
            'processing' => 'processing',
            default => 'unknown',
        };
    }

    // ======================== CASH PAYMENT ========================

    public function processCashPayment(Order $order): array
    {
        try {
            $payment = $order->payment;
            
            if (!$payment) {
                throw new \Exception('Aucun paiement associé à cette commande');
            }

            $transactionId = 'cash_' . strtoupper(uniqid());

            $payment->update([
                'transaction_id' => $transactionId,
                'status' => 'pending',
                'payment_data' => [
                    'type' => 'cash_on_delivery',
                    'initiated_at' => now()->toDateTimeString(),
                    'payment_pending_at_delivery' => true,
                ],
            ]);

            $order->update(['status' => 'confirmed']);

            Log::info('Paiement en espèces enregistré', [
                'order_id' => $order->id,
                'transaction_id' => $transactionId,
            ]);

            return [
                'success' => true,
                'payment_id' => $payment->id,
                'transaction_id' => $transactionId,
                'message' => 'Paiement en espèces enregistré. À payer à la livraison.',
                'amount' => $order->total,
            ];
        } catch (\Exception $e) {
            Log::error('Erreur paiement en espèces', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    // ======================== WEBHOOK HANDLERS ========================

    public function handleStripeWebhookEvent($event): void
    {
        switch ($event->type) {
            case 'payment_intent.succeeded':
                $this->handleStripePaymentSucceeded($event->data->object);
                break;
            case 'payment_intent.payment_failed':
                $this->handleStripePaymentFailed($event->data->object);
                break;
        }
    }

    private function handleStripePaymentSucceeded($paymentIntent): void
    {
        $orderId = $paymentIntent->metadata->order_id ?? null;

        if ($orderId) {
            $order = Order::find($orderId);
            if ($order && $order->payment) {
                $order->payment->update([
                    'status' => 'completed',
                    'payment_data' => array_merge(
                        $order->payment->payment_data ?? [],
                        [
                            'webhook_confirmed_at' => now()->toDateTimeString(),
                            'payment_method' => $paymentIntent->payment_method ?? null,
                        ]
                    ),
                ]);
                $order->update(['status' => 'confirmed']);

                Log::info('Stripe paiement réussi via webhook', [
                    'order_id' => $orderId,
                    'payment_intent' => $paymentIntent->id,
                ]);
            }
        }
    }

    private function handleStripePaymentFailed($paymentIntent): void
    {
        $orderId = $paymentIntent->metadata->order_id ?? null;

        if ($orderId) {
            $order = Order::find($orderId);
            if ($order && $order->payment) {
                $order->payment->markAsFailed(
                    'Paiement échoué: ' . ($paymentIntent->last_payment_error->message ?? 'Erreur inconnue')
                );

                Log::error('Stripe paiement échoué via webhook', [
                    'order_id' => $orderId,
                    'error' => $paymentIntent->last_payment_error->message ?? 'Unknown',
                ]);
            }
        }
    }

    public function handleFedaPayWebhookEvent(array $payload): void
    {
        $eventType = $payload['event'] ?? null;
        $transaction = $payload['transaction'] ?? null;

        if (!$eventType || !$transaction) {
            Log::warning('FedaPay webhook invalide', ['payload' => $payload]);
            return;
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
        }
    }

    private function handleFedaPayApproved(array $transaction): void
    {
        $transactionId = $transaction['id'] ?? null;

        if ($transactionId) {
            $payment = Payment::where('transaction_id', $transactionId)->first();
            
            if ($payment) {
                $payment->markAsCompleted();
                $payment->order->update(['status' => 'confirmed']);

                Log::info('FedaPay paiement approuvé', [
                    'transaction_id' => $transactionId,
                    'order_id' => $payment->order_id,
                ]);
            }
        }
    }

    private function handleFedaPayDeclined(array $transaction): void
    {
        $transactionId = $transaction['id'] ?? null;

        if ($transactionId) {
            $payment = Payment::where('transaction_id', $transactionId)->first();
            
            if ($payment) {
                $payment->markAsFailed('Paiement refusé par le fournisseur Mobile Money');

                Log::error('FedaPay paiement refusé', [
                    'transaction_id' => $transactionId,
                    'order_id' => $payment->order_id,
                ]);
            }
        }
    }

    private function handleFedaPayCanceled(array $transaction): void
    {
        $transactionId = $transaction['id'] ?? null;

        if ($transactionId) {
            $payment = Payment::where('transaction_id', $transactionId)->first();
            
            if ($payment) {
                $payment->markAsFailed('Paiement annulé par l\'utilisateur');

                Log::warning('FedaPay paiement annulé', [
                    'transaction_id' => $transactionId,
                    'order_id' => $payment->order_id,
                ]);
            }
        }
    }
}