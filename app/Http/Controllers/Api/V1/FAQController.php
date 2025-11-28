<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\FAQ;

/**
 * @OA\Tag(
 *     name="FAQ",
 *     description="Endpoints pour les questions fréquemment posées"
 * )
 */
class FAQController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/faqs",
     *     summary="Liste des FAQ actives",
     *     tags={"FAQ"},
     *     @OA\Response(
     *         response=200,
     *         description="Liste des FAQ",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object", @OA\Property(property="faqs", type="array", @OA\Items(type="object")))
     *         )
     *     )
     * )
     */
    public function index()
    {
        $faqs = FAQ::active()->get();

        return response()->json([
            'success' => true,
            'data' => [
                'faqs' => $faqs,
            ]
        ]);
    }
}
