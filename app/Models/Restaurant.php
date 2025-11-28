<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Restaurant extends Model
{
    protected $fillable = [
        'name',
        'description',
        'history',
        'values',
        'chef_name',
        'team_description',
        'phone',
        'email',
        'address',
        'latitude',
        'longitude',
        'opening_hours',
        'social_media',
        'delivery_radius_km',
        'delivery_fee_base',
        'delivery_fee_per_km',
        'minimum_order_amount',
        'logo',
        'images',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'opening_hours' => 'array',
            'social_media' => 'array',
            'images' => 'array',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'delivery_fee_base' => 'decimal:2',
            'delivery_fee_per_km' => 'decimal:2',
            'minimum_order_amount' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    // Relations
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
