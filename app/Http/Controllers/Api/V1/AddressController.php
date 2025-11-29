<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CreateAddressRequest;
use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Adresses",
 *     description="Endpoints pour la gestion des adresses de livraison"
 * )
 */
class AddressController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/addresses",
     *     summary="Liste des adresses de l'utilisateur",
     *     tags={"Adresses"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Liste des adresses",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object", @OA\Property(property="addresses", type="array", @OA\Items(type="object")))
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $addresses = $request->user()->addresses()->orderBy('is_default', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'addresses' => $addresses,
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/addresses",
     *     summary="Créer une nouvelle adresse",
     *     tags={"Adresses"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"label", "street", "city", "latitude", "longitude"},
     *             @OA\Property(property="label", type="string", example="Domicile"),
     *             @OA\Property(property="street", type="string", example="123 Rue de la Paix"),
     *             @OA\Property(property="city", type="string", example="Cotonou"),
     *             @OA\Property(property="postal_code", type="string", example="01 BP 1234"),
     *             @OA\Property(property="country", type="string", example="Bénin"),
     *             @OA\Property(property="latitude", type="number", format="float", example=6.372477),
     *             @OA\Property(property="longitude", type="number", format="float", example=2.354006),
     *             @OA\Property(property="additional_info", type="string", example="Près du marché"),
     *             @OA\Property(property="is_default", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Adresse créée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Adresse créée avec succès"),
     *             @OA\Property(property="data", type="object", @OA\Property(property="address", type="object"))
     *         )
     *     )
     * )
     */
    public function store(CreateAddressRequest $request)
    {
        // Si c'est la première adresse ou si is_default est true, mettre les autres à false
        if ($request->is_default || $request->user()->addresses()->count() === 0) {
            $request->user()->addresses()->update(['is_default' => false]);
            $request->merge(['is_default' => true]);
        }

        $address = $request->user()->addresses()->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Adresse créée avec succès',
            'data' => [
                'address' => $address,
            ]
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/addresses/{id}",
     *     summary="Détails d'une adresse",
     *     tags={"Adresses"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de l'adresse",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Détails de l'adresse",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object", @OA\Property(property="address", type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Adresse non trouvée"
     *     )
     * )
     */
    public function show(Request $request, $id)
    {
        $address = $request->user()->addresses()->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'address' => $address,
            ]
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/v1/addresses/{id}",
     *     summary="Mettre à jour une adresse",
     *     tags={"Adresses"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de l'adresse",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="label", type="string", example="Bureau"),
     *             @OA\Property(property="street", type="string", example="456 Avenue de la République"),
     *             @OA\Property(property="city", type="string", example="Cotonou"),
     *             @OA\Property(property="latitude", type="number", format="float", example=6.372477),
     *             @OA\Property(property="longitude", type="number", format="float", example=2.354006),
     *             @OA\Property(property="is_default", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Adresse mise à jour",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Adresse mise à jour"),
     *             @OA\Property(property="data", type="object", @OA\Property(property="address", type="object"))
     *         )
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $address = $request->user()->addresses()->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'label' => 'nullable|string|max:255',
            'street' => 'sometimes|string|max:255',
            'city' => 'sometimes|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'latitude' => 'sometimes|numeric',
            'longitude' => 'sometimes|numeric',
            'additional_info' => 'nullable|string|max:500',
            'is_default' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        if ($request->is_default) {
            $request->user()->addresses()->where('id', '!=', $id)->update(['is_default' => false]);
        }

        $address->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Adresse mise à jour',
            'data' => [
                'address' => $address->fresh(),
            ]
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/addresses/{id}",
     *     summary="Supprimer une adresse",
     *     tags={"Adresses"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de l'adresse",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Adresse supprimée",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Adresse supprimée")
     *         )
     *     )
     * )
     */
    public function destroy(Request $request, $id)
    {
        $address = $request->user()->addresses()->findOrFail($id);
        $address->delete();

        return response()->json([
            'success' => true,
            'message' => 'Adresse supprimée',
        ]);
    }
}
