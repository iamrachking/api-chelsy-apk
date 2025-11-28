<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PromoCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Codes Promo",
 *     description="Endpoints pour la validation des codes promotionnels"
 * )
 */
class PromoCodeController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/v1/promo-codes/validate",
     *     summary="Valider un code promo",
     *     tags={"Codes Promo"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"code", "order_amount"},
     *             @OA\Property(property="code", type="string", example="PROMO10"),
     *             @OA\Property(property="order_amount", type="number", format="float", example=50000)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Code promo valide",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Code promo valide"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="promo_code", type="object"),
     *                 @OA\Property(property="discount_amount", type="number", format="float", example=5000)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Code promo invalide ou non utilisable"
     *     )
     * )
     */
    public function validate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|exists:promo_codes,code',
            'order_amount' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        $promoCode = PromoCode::where('code', $request->code)->first();

        if (!$promoCode->isValidForUser($request->user()->id, $request->order_amount)) {
            return response()->json([
                'success' => false,
                'message' => 'Ce code promo n\'est pas valide ou ne peut pas être utilisé',
            ], 422);
        }

        $discount = $promoCode->calculateDiscount($request->order_amount);

        return response()->json([
            'success' => true,
            'message' => 'Code promo valide',
            'data' => [
                'promo_code' => $promoCode,
                'discount_amount' => $discount,
            ]
        ]);
    }
}
