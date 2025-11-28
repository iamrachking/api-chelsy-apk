<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    protected $fillable = [
        'cart_id',
        'dish_id',
        'quantity',
        'unit_price',
        'selected_options',
        'special_instructions',
    ];

    protected function casts(): array
    {
        return [
            'selected_options' => 'array',
            'unit_price' => 'decimal:2',
        ];
    }

    // Relations
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function dish(): BelongsTo
    {
        return $this->belongsTo(Dish::class);
    }

    // Calculs
    public function getTotalPriceAttribute(): float
    {
        return $this->unit_price * $this->quantity;
    }
}
