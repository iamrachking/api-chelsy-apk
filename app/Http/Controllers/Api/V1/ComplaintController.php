<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Réclamations",
 *     description="Endpoints pour la gestion des réclamations"
 * )
 */
class ComplaintController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/complaints",
     *     summary="Liste des réclamations de l'utilisateur",
     *     tags={"Réclamations"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Liste des réclamations",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object", @OA\Property(property="complaints", type="array", @OA\Items(type="object")))
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $complaints = $request->user()->complaints()
            ->with('order')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'complaints' => $complaints,
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/complaints",
     *     summary="Créer une réclamation",
     *     tags={"Réclamations"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"subject", "message"},
     *             @OA\Property(property="order_id", type="integer", nullable=true, example=1),
     *             @OA\Property(property="subject", type="string", example="Problème avec ma commande"),
     *             @OA\Property(property="message", type="string", example="Ma commande n'est pas arrivée à temps")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Réclamation créée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Réclamation soumise avec succès"),
     *             @OA\Property(property="data", type="object", @OA\Property(property="complaint", type="object"))
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'nullable|exists:orders,id',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        // Vérifier que la commande appartient à l'utilisateur si order_id est fourni
        if ($request->order_id) {
            $order = \App\Models\Order::where('id', $request->order_id)
                ->where('user_id', $request->user()->id)
                ->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'La commande spécifiée n\'existe pas ou ne vous appartient pas',
                ], 403);
            }
        }

        $complaint = Complaint::create([
            'user_id' => $request->user()->id,
            'order_id' => $request->order_id,
            'subject' => $request->subject,
            'message' => $request->message,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Réclamation soumise avec succès',
            'data' => [
                'complaint' => $complaint->load('order'),
            ]
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/complaints/{id}",
     *     summary="Détails d'une réclamation",
     *     tags={"Réclamations"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la réclamation",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Détails de la réclamation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object", @OA\Property(property="complaint", type="object"))
     *         )
     *     )
     * )
     */
    public function show(Request $request, $id)
    {
        $complaint = $request->user()->complaints()
            ->with('order')
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'complaint' => $complaint,
            ]
        ]);
    }
}
