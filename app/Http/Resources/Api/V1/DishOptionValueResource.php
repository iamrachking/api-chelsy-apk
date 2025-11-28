<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DishOptionValueResource extends JsonResource
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
            'dish_option_id' => $this->dish_option_id,
            'value' => $this->value,
            'price_modifier' => (float) $this->price_modifier,
            'order' => $this->order,
        ];
    }
}
