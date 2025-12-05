<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\BannerResource;
use App\Models\Banner;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Bannières",
 *     description="Endpoints pour les bannières promotionnelles"
 * )
 */
class BannerController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/banners",
     *     summary="Liste des bannières actives",
     *     tags={"Bannières"},
     *     @OA\Response(
     *         response=200,
     *         description="Liste des bannières",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="banners", type="array", @OA\Items(type="object"))
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        $banners = Banner::where('is_active', true)
            ->orderBy('order')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'banners' => BannerResource::collection($banners),
            ]
        ]);
    }
}

