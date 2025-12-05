<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    protected $summary = false;

    /**
     * Créer une instance avec mode summary (allégé)
     */
    public static function makeSummary($resource)
    {
        $instance = new static($resource);
        $instance->summary = true;
        return $instance;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Utiliser la version allégée par défaut pour toutes les commandes
        return $this->toSummaryArray($request);
    }

    /**
     * Version allégée pour toutes les commandes
     */
    protected function toSummaryArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'restaurant_id' => $this->restaurant_id,
            'restaurant' => $this->whenLoaded('restaurant', function () {
                $logoUrl = asset('images/default_restaurant.png');
                if ($this->restaurant->logo && \Illuminate\Support\Facades\Storage::disk('public')->exists($this->restaurant->logo)) {
                    // Générer une URL complète pour l'application mobile
                    $logoUrl = url(\Illuminate\Support\Facades\Storage::url($this->restaurant->logo));
                }
                
                return [
                    'id' => $this->restaurant->id,
                    'name' => $this->restaurant->name,
                    'phone' => $this->restaurant->phone,
                    'logo' => $logoUrl,
                ];
            }),
            'address_id' => $this->address_id,
            'address' => $this->whenLoaded('address', function () {
                if (!$this->address) {
                    return null;
                }
                return [
                    'id' => $this->address->id,
                    'label' => $this->address->label,
                    'street' => $this->address->street,
                    'city' => $this->address->city,
                    'latitude' => (float) $this->address->latitude,
                    'longitude' => (float) $this->address->longitude,
                ];
            }),
            'type' => $this->type,
            'status' => $this->status,
            'subtotal' => (float) $this->subtotal,
            'delivery_fee' => (float) $this->delivery_fee,
            'discount_amount' => (float) $this->discount_amount,
            'total' => (float) $this->total,
            'promo_code' => $this->whenLoaded('promoCode', function () {
                if (!$this->promoCode) {
                    return null;
                }
                return [
                    'code' => $this->promoCode->code,
                    'name' => $this->promoCode->name,
                ];
            }),
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'special_instructions' => $this->special_instructions,
            'items' => $this->whenLoaded('items', function () {
                return $this->items->map(function ($item) {
                    $dishImage = asset('images/default_dish.png');
                    if ($item->relationLoaded('dish') && $item->dish) {
                        $dishImages = $item->dish->images ?? [];
                        if (is_array($dishImages) && !empty($dishImages)) {
                            foreach ($dishImages as $imagePath) {
                                if ($imagePath && \Illuminate\Support\Facades\Storage::disk('public')->exists($imagePath)) {
                                    // Générer une URL complète pour l'application mobile
                                    $dishImage = url(\Illuminate\Support\Facades\Storage::url($imagePath));
                                    break;
                                }
                            }
                        }
                    }
                    
                    return [
                        'id' => $item->id,
                        'dish_id' => $item->dish_id,
                        'dish_name' => $item->dish_name,
                        'quantity' => (int) $item->quantity,
                        'unit_price' => (float) $item->unit_price,
                        'total_price' => (float) $item->total_price,
                        'special_instructions' => $item->special_instructions,
                        'dish' => $item->relationLoaded('dish') && $item->dish ? [
                            'id' => $item->dish->id,
                            'name' => $item->dish->name,
                            'image' => $dishImage,
                            'price' => (float) $item->dish->price,
                        ] : null,
                    ];
                });
            }),
            'payment' => $this->whenLoaded('payment', function () {
                if (!$this->payment) {
                    return null;
                }
                return [
                    'method' => $this->payment->method,
                    'status' => $this->payment->status,
                    'amount' => (float) $this->payment->amount,
                ];
            }),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
