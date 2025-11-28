<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DishOptionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'dish_id' => $this->dish_id,
            'name' => $this->name,
            'type' => $this->type,
            'is_required' => $this->is_required,
            'order' => $this->order,
            'values' => $this->whenLoaded('values', function () {
                return DishOptionValueResource::collection($this->values);
            }),
        ];
    }
}
