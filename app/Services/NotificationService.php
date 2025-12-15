<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Firebase\JWT\JWT;

class NotificationService
{
    private const FCM_URL = 'https://fcm.googleapis.com/v1/projects/{project_id}/messages:send';

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
        try {
            // Obtenir le token d'accès
            $accessToken = $this->getAccessToken();
            
            if (!$accessToken) {
                Log::error('Impossible d\'obtenir le token d\'accès Firebase');
                return false;
            }

            // Obtenir l'ID du projet
            $projectId = config('services.firebase.project_id', 'chelsy-restaurant');

            // Construire le payload
            $payload = [
                'message' => [
                    'token' => $token,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'data' => $data,
                    'webpush' => [
                        'fcmOptions' => [
                            'link' => 'FLUTTER_NOTIFICATION_CLICK',
                        ],
                    ],
                    'android' => [
                        'notification' => [
                            'sound' => 'default',
                            'clickAction' => 'FLUTTER_NOTIFICATION_CLICK',
                        ],
                    ],
                    'apns' => [
                        'headers' => [
                            'apns-priority' => '10',
                        ],
                    ],
                ],
            ];

            // Envoyer la notification
            $url = str_replace('{project_id}', $projectId, self::FCM_URL);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ])->timeout(10)->post($url, $payload);

            if ($response->successful()) {
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
     * Obtenir un token d'accès Firebase à partir du Service Account
     */
    private function getAccessToken(): ?string
    {
        try {
            $credentialsPath = config('services.firebase.credentials_path');

            if (!$credentialsPath || !file_exists($credentialsPath)) {
                Log::error('Firebase credentials file not found: ' . $credentialsPath);
                return null;
            }

            $credentials = json_decode(file_get_contents($credentialsPath), true);

            if (!isset($credentials['private_key'], $credentials['client_email'], $credentials['project_id'])) {
                Log::error('Invalid Firebase credentials format');
                return null;
            }

            // Créer le JWT
            $now = time();
            $payload = [
                'iss' => $credentials['client_email'],
                'scope' => 'https://www.googleapis.com/auth/cloud-platform',
                'aud' => 'https://oauth2.googleapis.com/token',
                'exp' => $now + 3600,
                'iat' => $now,
            ];

            $jwt = JWT::encode($payload, $credentials['private_key'], 'RS256');

            // Échanger le JWT pour un token d'accès
            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            if (!$response->successful()) {
                Log::error('Failed to obtain access token', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            $data = $response->json();
            return $data['access_token'] ?? null;
        } catch (\Exception $e) {
            Log::error('Error getting access token', [
                'message' => $e->getMessage(),
            ]);
            return null;
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