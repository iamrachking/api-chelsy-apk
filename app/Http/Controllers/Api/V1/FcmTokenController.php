<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Notifications",
 *     description="Endpoints pour la gestion des tokens FCM (Firebase Cloud Messaging)"
 * )
 */
class FcmTokenController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/v1/fcm-token",
     *     summary="Enregistrer ou mettre à jour le token FCM",
     *     tags={"Notifications"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"token"},
     *             @OA\Property(property="token", type="string", example="fcm_token_string_from_firebase")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Token enregistré avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Token FCM enregistré avec succès")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Erreur de validation"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $user->update([
            'fcm_token' => $request->token,
            'fcm_token_updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Token FCM enregistré avec succès',
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/fcm-token",
     *     summary="Supprimer le token FCM (déconnexion)",
     *     tags={"Notifications"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Token supprimé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Token FCM supprimé avec succès")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié"
     *     )
     * )
     */
    public function destroy(Request $request)
    {
        $user = $request->user();
        $user->update([
            'fcm_token' => null,
            'fcm_token_updated_at' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Token FCM supprimé avec succès',
        ]);
    }
}

