<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ReviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Traiter les images : vérifier si elles existent et convertir en URLs complètes
        $images = [];
        $reviewImages = $this->images ?? [];
        
        if (is_array($reviewImages) && !empty($reviewImages)) {
            foreach ($reviewImages as $imagePath) {
                // Vérifier si l'image existe réellement dans le storage
                if ($imagePath && Storage::disk('public')->exists($imagePath)) {
                    // Générer une URL complète pour l'application mobile
                    $images[] = url(Storage::url($imagePath));
                }
            }
        }
        // Note: Pour les reviews, on retourne un tableau vide si pas d'images (pas d'image par défaut)

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user' => $this->whenLoaded('user', function () {
                return new UserResource($this->user);
            }),
            'order_id' => $this->order_id,
            'dish_id' => $this->dish_id,
            'dish' => $this->whenLoaded('dish', function () {
                return new DishResource($this->dish);
            }),
            'type' => $this->type,
            'rating' => (int) $this->rating,
            'comment' => $this->comment,
            'images' => $images,
            'restaurant_response' => $this->restaurant_response,
            'restaurant_response_at' => $this->restaurant_response_at?->toIso8601String(),
            'is_approved' => $this->is_approved,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
