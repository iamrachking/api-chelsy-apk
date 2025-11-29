<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class NotificationService
{
    /**
     * Envoyer une notification push à un utilisateur
     *
     * @param User $user
     * @param string $title
     * @param string $body
     * @param array $data
     * @return bool
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
     *
     * @param string $token
     * @param string $title
     * @param string $body
     * @param array $data
     * @return bool
     */
    public function sendToToken(string $token, string $title, string $body, array $data = []): bool
    {
        $credentialsPath = config('services.firebase.credentials_path');
        $serverKey = config('services.firebase.server_key');
        $projectId = config('services.firebase.project_id');
        
        // Utiliser le Service Account JSON si disponible, sinon utiliser la Server Key
        if ($credentialsPath && file_exists($credentialsPath)) {
            return $this->sendWithServiceAccount($token, $title, $body, $data, $credentialsPath, $projectId);
        } elseif ($serverKey) {
            return $this->sendWithServerKey($token, $title, $body, $data, $serverKey);
        } else {
            Log::error('Firebase non configuré : ni Service Account ni Server Key trouvés');
            return false;
        }
    }

    /**
     * Envoyer une notification en utilisant le Service Account (API v1 - moderne)
     */
    private function sendWithServiceAccount(string $token, string $title, string $body, array $data, string $credentialsPath, ?string $projectId): bool
    {
        try {
            $credentials = json_decode(file_get_contents($credentialsPath), true);
            
            if (!$credentials || !isset($credentials['private_key'])) {
                Log::error('Fichier de credentials Firebase invalide');
                return false;
            }

            // Obtenir un access token OAuth2
            $accessToken = $this->getAccessToken($credentials);
            
            if (!$accessToken) {
                return false;
            }

            // Utiliser l'API FCM v1
            $projectId = $projectId ?? $credentials['project_id'] ?? 'chelsy-restaurant';
            $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

            $message = [
                'message' => [
                    'token' => $token,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'data' => array_map('strval', array_merge([
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    ], $data)),
                    'android' => [
                        'priority' => 'high',
                    ],
                    'apns' => [
                        'headers' => [
                            'apns-priority' => '10',
                        ],
                    ],
                ],
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ])->post($url, $message);

            if ($response->successful()) {
                Log::info("Notification envoyée avec succès (Service Account)", [
                    'token' => substr($token, 0, 20) . '...',
                    'title' => $title,
                ]);
                return true;
            }

            // Vérifier les erreurs
            $errorBody = $response->json();
            if (isset($errorBody['error'])) {
                $errorCode = $errorBody['error']['code'] ?? '';
                if (in_array($errorCode, ['NOT_FOUND', 'INVALID_ARGUMENT'])) {
                    // Token invalide
                    User::where('fcm_token', $token)->update([
                        'fcm_token' => null,
                        'fcm_token_updated_at' => null,
                    ]);
                    Log::warning("Token FCM invalide supprimé: {$token}");
                }
            }

            Log::error('Erreur lors de l\'envoi de notification FCM (Service Account)', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error('Exception lors de l\'envoi de notification FCM (Service Account)', [
                'message' => $e->getMessage(),
                'token' => substr($token, 0, 20) . '...',
            ]);
            return false;
        }
    }

    /**
     * Obtenir un access token OAuth2 depuis le Service Account
     */
    private function getAccessToken(array $credentials): ?string
    {
        try {
            $jwt = $this->createJWT($credentials);
            
            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['access_token'] ?? null;
            }

            Log::error('Erreur lors de l\'obtention du token OAuth2', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return null;
        } catch (\Exception $e) {
            Log::error('Exception lors de l\'obtention du token OAuth2', [
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Créer un JWT pour l'authentification OAuth2
     */
    private function createJWT(array $credentials): string
    {
        $now = time();
        $header = [
            'alg' => 'RS256',
            'typ' => 'JWT',
        ];

        $payload = [
            'iss' => $credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => $credentials['token_uri'],
            'exp' => $now + 3600,
            'iat' => $now,
        ];

        $base64UrlHeader = $this->base64UrlEncode(json_encode($header));
        $base64UrlPayload = $this->base64UrlEncode(json_encode($payload));

        $signatureInput = $base64UrlHeader . '.' . $base64UrlPayload;
        $privateKey = openssl_pkey_get_private($credentials['private_key']);
        
        if (!$privateKey) {
            throw new \Exception('Impossible de charger la clé privée');
        }

        openssl_sign($signatureInput, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        $base64UrlSignature = $this->base64UrlEncode($signature);

        return $signatureInput . '.' . $base64UrlSignature;
    }

    /**
     * Encoder en base64 URL-safe
     */
    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Envoyer une notification en utilisant la Server Key (API Legacy - fallback)
     */
    private function sendWithServerKey(string $token, string $title, string $body, array $data, string $serverKey): bool
    {
        $payload = [
            'to' => $token,
            'notification' => [
                'title' => $title,
                'body' => $body,
                'sound' => 'default',
            ],
            'data' => array_merge([
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            ], $data),
            'priority' => 'high',
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'key=' . $serverKey,
                'Content-Type' => 'application/json',
            ])->post('https://fcm.googleapis.com/fcm/send', $payload);

            if ($response->successful()) {
                $result = $response->json();
                
                // Vérifier si le token est invalide
                if (isset($result['results'][0]['error'])) {
                    $error = $result['results'][0]['error'];
                    if (in_array($error, ['InvalidRegistration', 'NotRegistered'])) {
                        // Token invalide, le supprimer de l'utilisateur
                        User::where('fcm_token', $token)->update([
                            'fcm_token' => null,
                            'fcm_token_updated_at' => null,
                        ]);
                        Log::warning("Token FCM invalide supprimé: {$token}");
                    }
                    return false;
                }

                Log::info("Notification envoyée avec succès (Server Key)", [
                    'token' => substr($token, 0, 20) . '...',
                    'title' => $title,
                ]);
                return true;
            }

            Log::error('Erreur lors de l\'envoi de notification FCM (Server Key)', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error('Exception lors de l\'envoi de notification FCM (Server Key)', [
                'message' => $e->getMessage(),
                'token' => substr($token, 0, 20) . '...',
            ]);
            return false;
        }
    }

    /**
     * Envoyer une notification de changement de statut de commande
     *
     * @param User $user
     * @param \App\Models\Order $order
     * @param string $status
     * @return bool
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

        $title = 'Mise à jour de votre commande';
        $body = "Votre commande #{$order->order_number} est maintenant : " . ($statusLabels[$status] ?? $status);

        $data = [
            'type' => 'order_status_update',
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $status,
        ];

        return $this->sendToUser($user, $title, $body, $data);
    }

    /**
     * Envoyer une notification de réponse à une réclamation
     *
     * @param User $user
     * @param \App\Models\Complaint $complaint
     * @return bool
     */
    public function sendComplaintResponse(User $user, \App\Models\Complaint $complaint): bool
    {
        $title = 'Réponse à votre réclamation';
        $body = "Nous avons répondu à votre réclamation concernant : {$complaint->subject}";

        $data = [
            'type' => 'complaint_response',
            'complaint_id' => $complaint->id,
            'status' => $complaint->status,
        ];

        return $this->sendToUser($user, $title, $body, $data);
    }

    /**
     * Envoyer une notification de confirmation de paiement
     *
     * @param User $user
     * @param \App\Models\Order $order
     * @return bool
     */
    public function sendPaymentConfirmation(User $user, \App\Models\Order $order): bool
    {
        $title = 'Paiement confirmé';
        $body = "Votre paiement pour la commande #{$order->order_number} a été confirmé avec succès.";

        $data = [
            'type' => 'payment_confirmed',
            'order_id' => $order->id,
            'order_number' => $order->order_number,
        ];

        return $this->sendToUser($user, $title, $body, $data);
    }
}

