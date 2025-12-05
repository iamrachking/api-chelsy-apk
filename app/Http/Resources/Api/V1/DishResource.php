<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class DishResource extends JsonResource
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
        $dishImages = $this->images ?? [];
        
        if (is_array($dishImages) && !empty($dishImages)) {
            foreach ($dishImages as $imagePath) {
                // Vérifier si l'image existe réellement dans le storage
                if ($imagePath && Storage::disk('public')->exists($imagePath)) {
                    // Générer une URL complète pour l'application mobile
                    $images[] = url(Storage::url($imagePath));
                }
            }
        }
        
        // Si aucune image valide n'existe, utiliser l'image par défaut
        if (empty($images)) {
            $images = [asset('images/default_dish.png')];
        }
        
        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'category' => $this->whenLoaded('category', function () {
                return new CategoryResource($this->category);
            }),
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => (float) $this->price,
            'discount_price' => $this->discount_price ? (float) $this->discount_price : null,
            'final_price' => (float) $this->final_price,
            'has_discount' => $this->has_discount,
            'images' => $images,
            'image' => $images[0] ?? asset('images/default_dish.png'), // Image principale pour compatibilité
            'preparation_time_minutes' => $this->preparation_time_minutes,
            'allergens' => $this->allergens ?? [],
            'nutritional_info' => $this->nutritional_info ?? [],
            'is_available' => $this->is_available,
            'is_featured' => $this->is_featured,
            'is_new' => $this->is_new,
            'is_vegetarian' => $this->is_vegetarian,
            'is_specialty' => $this->is_specialty,
            'average_rating' => $this->average_rating ? (float) $this->average_rating : null,
            'review_count' => (int) $this->review_count,
            'order_count' => (int) $this->order_count,
            'options' => $this->whenLoaded('options', function () {
                return DishOptionResource::collection($this->options);
            }),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
