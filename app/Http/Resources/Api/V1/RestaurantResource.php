<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class RestaurantResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Convertir le logo en URL complète si elle existe, sinon utiliser l'image par défaut
        $logoUrl = asset('images/default_restaurant.png');
        if ($this->logo && Storage::disk('public')->exists($this->logo)) {
            // Générer une URL complète pour l'application mobile
            $logoUrl = url(Storage::url($this->logo));
        }

        // Traiter les images : vérifier si elles existent et convertir en URLs complètes
        $images = [];
        $restaurantImages = $this->images ?? [];
        
        if (is_array($restaurantImages) && !empty($restaurantImages)) {
            foreach ($restaurantImages as $imagePath) {
                // Vérifier si l'image existe réellement dans le storage
                if ($imagePath && Storage::disk('public')->exists($imagePath)) {
                    // Générer une URL complète pour l'application mobile
                    $images[] = url(Storage::url($imagePath));
                }
            }
        }
        
        // Si aucune image valide n'existe, utiliser l'image par défaut
        if (empty($images)) {
            $images = [asset('images/default_restaurant.png')];
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'history' => $this->history,
            'values' => $this->values,
            'chef_name' => $this->chef_name,
            'team_description' => $this->team_description,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
            'opening_hours' => $this->opening_hours,
            'social_media' => $this->social_media,
            'delivery_radius_km' => (int) $this->delivery_radius_km,
            'delivery_fee_base' => (float) $this->delivery_fee_base,
            'delivery_fee_per_km' => (float) $this->delivery_fee_per_km,
            'minimum_order_amount' => (float) $this->minimum_order_amount,
            'logo' => $logoUrl,
            'images' => $images,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
