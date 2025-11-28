<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    protected $fillable = [
        'user_id',
        'session_id',
    ];

    // Relations
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    // Calculs
    public function getSubtotalAttribute(): float
    {
        if (!$this->relationLoaded('items')) {
            $this->load('items');
        }
        return $this->items->sum(function ($item) {
            return $item->unit_price * $item->quantity;
        });
    }

    public function getTotalItemsAttribute(): int
    {
        if (!$this->relationLoaded('items')) {
            $this->load('items');
        }
        return $this->items->sum('quantity');
    }
}
