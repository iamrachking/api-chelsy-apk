<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class TestNotificationController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Envoyer une notification de test
     * Route: POST /api/v1/test-notification
     */
    public function sendTest(Request $request)
    {
        $user = $request->user();

        // Vérifier que l'utilisateur a un token FCM
        if (!$user->fcm_token) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun token FCM trouvé. Assurez-vous que les notifications sont activées dans l\'app.',
                'fcm_token' => null,
            ], 422);
        }

        // Envoyer la notification de test
        $success = $this->notificationService->sendTestNotification($user);

        return response()->json([
            'success' => $success,
            'message' => $success 
                ? 'Notification de test envoyée! Vérifiez votre téléphone.'
                : 'Erreur lors de l\'envoi de la notification.',
            'fcm_token' => substr($user->fcm_token, 0, 20) . '...',
            'token_updated_at' => $user->fcm_token_updated_at,
        ]);
    }
}