<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateProfileRequest;
use App\Http\Resources\Api\V1\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Profil Utilisateur",
 *     description="Endpoints pour la gestion du profil utilisateur"
 * )
 */
class UserController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/profile",
     *     summary="Récupérer le profil de l'utilisateur connecté",
     *     tags={"Profil Utilisateur"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Profil récupéré avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object", @OA\Property(property="user", type="object"))
     *         )
     *     )
     * )
     */
    public function profile(Request $request)
    {
        $user = $request->user()->load(['addresses', 'defaultAddress']);
        
        return response()->json([
            'success' => true,
            'data' => [
                'user' => new UserResource($user),
            ]
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/profile",
     *     summary="Mettre à jour le profil",
     *     tags={"Profil Utilisateur"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="firstname", type="string", example="John"),
     *             @OA\Property(property="lastname", type="string", example="Doe"),
     *             @OA\Property(property="phone", type="string", nullable=true, example="+229 12 34 56 78"),
     *             @OA\Property(property="birth_date", type="string", format="date", nullable=true),
     *             @OA\Property(property="gender", type="string", enum={"male", "female", "other"}, nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Profil mis à jour avec succès"
     *     )
     * )
     */
    public function updateProfile(UpdateProfileRequest $request)
{
    $user = $request->user();

    // Si un fichier avatar est uploadé
    if ($request->hasFile('avatar')) {
        $file = $request->file('avatar');
        $path = $file->store('avatars', 'public'); // Stocke dans storage/app/public/avatars
        $user->avatar = $path;
    }

    // Mettre à jour les autres champs
    $user->update($request->except('avatar'));

    return response()->json([
        'success' => true,
        'message' => 'Profil mis à jour avec succès',
        'data' => [
            'user' => new UserResource($user->fresh()), // renvoie l'URL complète
        ]
    ]);
}


    /**
     * @OA\Post(
     *     path="/api/v1/change-password",
     *     summary="Changer le mot de passe",
     *     tags={"Profil Utilisateur"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"current_password", "password", "password_confirmation"},
     *             @OA\Property(property="current_password", type="string", format="password", example="oldpassword"),
     *             @OA\Property(property="password", type="string", format="password", example="newpassword123"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="newpassword123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Mot de passe modifié avec succès"
     *     )
     * )
     */
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Mot de passe actuel incorrect',
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Mot de passe modifié avec succès',
        ]);
    }
}
