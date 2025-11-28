<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    protected $fillable = [
        'user_id',
        'order_id',
        'dish_id',
        'type',
        'rating',
        'comment',
        'images',
        'restaurant_response',
        'restaurant_response_at',
        'is_approved',
    ];

    protected function casts(): array
    {
        return [
            'images' => 'array',
            'restaurant_response_at' => 'datetime',
            'is_approved' => 'boolean',
        ];
    }

    // Relations
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function dish(): BelongsTo
    {
        return $this->belongsTo(Dish::class);
    }

    // Scopes
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    public function scopeForDish($query)
    {
        return $query->where('type', 'dish');
    }

    public function scopeForRestaurant($query)
    {
        return $query->where('type', 'restaurant');
    }
}
