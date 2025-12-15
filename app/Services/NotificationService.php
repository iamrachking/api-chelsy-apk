<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class NotificationService
{
    /**
     * Envoyer une notification push à un utilisateur
     */
    public function sendToUser(User $user, string $title, string $body, array $data = []): bool
    {
        if (!$user->fcm_token) {
            Log::info("Aucun token FCM pour l'utilisateur {$user->id}");
            return false;
        }

        return $this->sendToToken($user->fcm_token, $title, $body, $data);
    }

    /**
     * Envoyer une notification push à un token FCM spécifique
     */
    public function sendToToken(string $token, string $title, string $body, array $data = []): bool
    {
        $serverKey = config('services.firebase.server_key');
        
        if (!$serverKey) {
            Log::error('Firebase Server Key non configurée');
            return false;
        }

        return $this->sendWithServerKey($token, $title, $body, $data, $serverKey);
    }

    /**
     * Envoyer une notification en utilisant la Server Key (API Legacy)
     */
    private function sendWithServerKey(string $token, string $title, string $body, array $data, string $serverKey): bool
    {
        $payload = [
            'to' => $token,
            'notification' => [
                'title' => $title,
                'body' => $body,
                'sound' => 'default',
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            ],
            'data' => array_merge([
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            ], $data),
            'priority' => 'high',
            'time_to_live' => 86400,
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'key=' . $serverKey,
                'Content-Type' => 'application/json',
            ])->timeout(10)->post('https://fcm.googleapis.com/fcm/send', $payload);

            if ($response->successful()) {
                $result = $response->json();
                
                if (isset($result['results'][0]['error'])) {
                    $error = $result['results'][0]['error'];
                    if (in_array($error, ['InvalidRegistration', 'NotRegistered', 'MissingRegistration'])) {
                        User::where('fcm_token', $token)->update([
                            'fcm_token' => null,
                            'fcm_token_updated_at' => null,
                        ]);
                        Log::warning("Token FCM invalide supprimé: {$token}");
                    }
                    return false;
                }

                Log::info("✅ Notification envoyée avec succès", [
                    'token' => substr($token, 0, 20) . '...',
                    'title' => $title,
                ]);
                return true;
            }

            Log::error('❌ Erreur FCM', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error('❌ Exception FCM', [
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Envoyer une notification de changement de statut de commande
     */
    public function sendOrderStatusUpdate(User $user, \App\Models\Order $order, string $status): bool
    {
        $statusLabels = [
            'pending' => 'En attente',
            'confirmed' => 'Confirmée',
            'preparing' => 'En préparation',
            'ready' => 'Prête',
            'out_for_delivery' => 'En livraison',
            'delivered' => 'Livrée',
            'picked_up' => 'Récupérée',
            'cancelled' => 'Annulée',
        ];

        $title = '📦 Mise à jour de commande';
        $statusLabel = $statusLabels[$status] ?? $status;
        $body = "Votre commande #{$order->order_number} est {$statusLabel}";

        $data = [
            'type' => 'order_status_update',
            'order_id' => (string) $order->id,
            'order_number' => $order->order_number,
            'status' => $status,
        ];

        Log::info("Envoi notification statut commande: {$order->id} -> {$status}");
        return $this->sendToUser($user, $title, $body, $data);
    }

    /**
     * Envoyer une notification de confirmation de paiement
     */
    public function sendPaymentConfirmation(User $user, \App\Models\Order $order): bool
    {
        $title = '✅ Paiement confirmé';
        $body = "Votre paiement pour la commande #{$order->order_number} a été validé.";

        $data = [
            'type' => 'payment_confirmed',
            'order_id' => (string) $order->id,
            'order_number' => $order->order_number,
        ];

        Log::info("Envoi notification paiement: {$order->id}");
        return $this->sendToUser($user, $title, $body, $data);
    }

    /**
     * Envoyer une notification de création de commande
     */
    public function sendOrderCreated(User $user, \App\Models\Order $order): bool
    {
        $title = '🎉 Commande créée';
        $totalFormatted = number_format($order->total, 0, ',', ' ');
        $body = "Votre commande #{$order->order_number} a été créée. Total: {$totalFormatted} FCFA";

        $data = [
            'type' => 'order_created',
            'order_id' => (string) $order->id,
            'order_number' => $order->order_number,
        ];

        Log::info("Envoi notification création: {$order->id}");
        return $this->sendToUser($user, $title, $body, $data);
    }

    /**
     * Envoyer une notification de réponse à une réclamation
     */
    public function sendComplaintResponse(User $user, $complaint): bool
    {
        $title = '💬 Réponse à votre réclamation';
        $body = "Nous avons répondu concernant: {$complaint->subject}";

        $data = [
            'type' => 'complaint_response',
            'complaint_id' => (string) $complaint->id,
        ];

        Log::info("Envoi notification réclamation: {$complaint->id}");
        return $this->sendToUser($user, $title, $body, $data);
    }

    /**
     * Envoyer une notification de bienvenue
     */
    public function sendWelcome(User $user): bool
    {
        $title = '👋 Bienvenue sur CHELSY!';
        $body = "Merci de vous être inscrit. Découvrez nos délicieux plats!";

        $data = [
            'type' => 'welcome',
        ];

        Log::info("Envoi notification bienvenue: {$user->id}");
        return $this->sendToUser($user, $title, $body, $data);
    }

    /**
     * Tester une notification
     */
    public function sendTestNotification(User $user): bool
    {
        $title = '🧪 Notification de test';
        $body = 'Ceci est une notification de test. Si vous la voyez, FCM fonctionne!';

        $data = [
            'type' => 'test',
            'timestamp' => now()->toIso8601String(),
        ];

        Log::info("Envoi notification TEST: {$user->id}");
        return $this->sendToUser($user, $title, $body, $data);
    }
}