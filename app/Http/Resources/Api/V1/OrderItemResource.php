<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
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
            'order_id' => $this->order_id,
            'dish_id' => $this->dish_id,
            'dish' => $this->whenLoaded('dish', function () {
                return new DishResource($this->dish);
            }),
            'dish_name' => $this->dish_name,
            'quantity' => (int) $this->quantity,
            'unit_price' => (float) $this->unit_price,
            'total_price' => (float) $this->total_price,
            'selected_options' => $this->selected_options ?? [],
            'special_instructions' => $this->special_instructions,
        ];
    }
}
