<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class CategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Convertir l'image en URL complète si elle existe, sinon utiliser l'image par défaut
        $imageUrl = asset('images/default_category.png');
        if ($this->image && Storage::disk('public')->exists($this->image)) {
            // Générer une URL complète pour l'application mobile
            $imageUrl = url(Storage::url($this->image));
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'image' => $imageUrl,
            'order' => $this->order,
            'is_active' => $this->is_active,
            'dishes_count' => $this->when(isset($this->dishes_count), $this->dishes_count),
            'dishes' => $this->whenLoaded('dishes', function () {
                return DishResource::collection($this->dishes);
            }),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
