<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class BannerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Convertir l'image en URL complète
        $imageUrl = asset('images/default_banner.png');
        if ($this->image && Storage::disk('public')->exists($this->image)) {
            // Générer une URL complète pour l'application mobile
            $imageUrl = url(Storage::url($this->image));
        }

        return [
            'id' => $this->id,
            'title' => $this->title,
            'image' => $imageUrl,
            'link' => $this->link,
            'order' => $this->order,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}

