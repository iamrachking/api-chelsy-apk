<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Exception\ApiErrorException;
use FedaPay\FedaPay;
use FedaPay\Transaction;

class PaymentService
{
    public function __construct()
    {
        // Configuration Stripe
        $stripeSecret = config('services.stripe.secret');
        if ($stripeSecret) {
            Stripe::setApiKey($stripeSecret);
        }

        // Configuration FedaPay
        $fedaPaySecret = config('services.fedapay.secret_key');
        $fedaPayEnv = config('services.fedapay.environment');
        if ($fedaPaySecret) {
            FedaPay::setApiKey($fedaPaySecret);
            FedaPay::setEnvironment($fedaPayEnv);
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
            $payment = $order->payment;
            
            if (!$payment) {
                throw new \Exception('Aucun paiement associé à cette commande');
            }

            $paymentIntent = PaymentIntent::create([
                'amount' => (int)($order->total * 100), // Convertir en centimes
                'currency' => 'xof', // Franc CFA
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

            // Mettre à jour le paiement
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
                'code' => $e->getCode(),
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
                            [
                                'confirmed_at' => now()->toDateTimeString(),
                                'payment_method' => $paymentIntent->payment_method ?? null,
                            ]
                        ),
                    ]);

                    // Mettre à jour le statut de la commande
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
     * Traiter un paiement Mobile Money via FedaPay
     * 
     * @param Order $order
     * @param string $provider (mtn, moov)
     * @param string $phoneNumber
     * @return array
     */
    public function processMobileMoneyPayment(Order $order, string $provider, string $phoneNumber): array
    {
        try {
            $payment = $order->payment;
            
            if (!$payment) {
                throw new \Exception('Aucun paiement associé à cette commande');
            }

            // Normaliser le numéro de téléphone
            $phoneNumber = $this->normalizePhoneNumber($phoneNumber);

            // Valider le fournisseur
            $provider = strtolower($provider);
            if (!in_array($provider, ['mtn', 'moov'])) {
                throw new \Exception('Fournisseur Mobile Money non valide. Utilisez MTN ou Moov.');
            }

            // Créer la transaction FedaPay
            $transaction = Transaction::create([
                'description' => "Commande {$order->order_number} - Restaurant Chelsy",
                'amount' => (int)$order->total,
                'currency' => ['iso' => 'XOF'],
                'callback_url' => route('api.v1.webhooks.fedapay'),
                'customer' => [
                    'firstname' => $order->user->first_name ?? 'Client',
                    'lastname' => $order->user->last_name ?? '',
                    'email' => $order->user->email,
                    'phone_number' => [
                        'number' => $phoneNumber,
                        'country' => 'bj'
                    ]
                ],
            ]);

            // Générer le token de paiement
            $token = $transaction->generateToken();

            // Mettre à jour le paiement
            $payment->update([
                'mobile_money_provider' => $provider,
                'mobile_money_number' => $phoneNumber,
                'transaction_id' => $transaction->id,
                'payment_data' => [
                    'fedapay_transaction_id' => $transaction->id,
                    'fedapay_token' => $token->token,
                    'fedapay_url' => $token->url,
                    'provider' => $provider,
                    'phone' => $phoneNumber,
                    'initiated_at' => now()->toDateTimeString(),
                ],
            ]);

            return [
                'success' => true,
                'message' => 'Paiement Mobile Money initié avec succès',
                'transaction_id' => $transaction->id,
                'payment_url' => $token->url,
                'token' => $token->token,
            ];
        } catch (\Exception $e) {
            Log::error('FedaPay Mobile Money Error', [
                'order_id' => $order->id,
                'provider' => $provider ?? null,
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
     * 
     * @param Order $order
     * @return array
     */
    public function processCashPayment(Order $order): array
    {
        $payment = $order->payment;
        
        if ($payment) {
            $payment->update([
                'status' => 'pending',
                'payment_data' => [
                    'type' => 'cash_on_delivery',
                    'initiated_at' => now()->toDateTimeString(),
                ],
            ]);
        }

        return [
            'success' => true,
            'message' => 'Paiement en espèces enregistré. À payer à la livraison.',
        ];
    }

    /**
     * Vérifier le statut d'une transaction FedaPay
     * 
     * @param string $transactionId
     * @return array
     */
    public function checkFedaPayStatus(string $transactionId): array
    {
        try {
            $transaction = Transaction::retrieve($transactionId);

            return [
                'success' => true,
                'status' => $transaction->status,
                'transaction' => $transaction,
            ];
        } catch (\Exception $e) {
            Log::error('FedaPay Status Check Error', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Normaliser un numéro de téléphone béninois
     * 
     * @param string $phoneNumber
     * @return string
     */
    private function normalizePhoneNumber(string $phoneNumber): string
    {
        // Supprimer tous les espaces et caractères spéciaux
        $phone = preg_replace('/[^0-9+]/', '', $phoneNumber);

        // Si le numéro commence par +229, on le garde
        if (str_starts_with($phone, '+229')) {
            return $phone;
        }

        // Si le numéro commence par 229, on ajoute le +
        if (str_starts_with($phone, '229')) {
            return '+' . $phone;
        }

        // Si le numéro commence par 0, on remplace par +229
        if (str_starts_with($phone, '0')) {
            return '+229' . substr($phone, 1);
        }

        // Sinon, on ajoute +229 devant
        return '+229' . $phone;
    }

    /**
     * Rembourser un paiement Stripe
     * 
     * @param Payment $payment
     * @param float|null $amount
     * @return array
     */
    public function refundStripePayment(Payment $payment, ?float $amount = null): array
    {
        try {
            if ($payment->method !== 'card' || !$payment->transaction_id) {
                throw new \Exception('Ce paiement ne peut pas être remboursé via Stripe');
            }

            $refundData = [
                'payment_intent' => $payment->transaction_id,
            ];

            if ($amount) {
                $refundData['amount'] = (int)($amount * 100);
            }

            $refund = \Stripe\Refund::create($refundData);

            $payment->update([
                'status' => 'refunded',
                'payment_data' => array_merge(
                    $payment->payment_data ?? [],
                    [
                        'refund_id' => $refund->id,
                        'refunded_at' => now()->toDateTimeString(),
                        'refund_amount' => $amount ?? $payment->amount,
                    ]
                ),
            ]);

            return [
                'success' => true,
                'message' => 'Remboursement effectué avec succès',
                'refund_id' => $refund->id,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Stripe Refund Error', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}