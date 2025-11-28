<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PromoCode extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'type',
        'value',
        'minimum_order_amount',
        'max_uses',
        'max_uses_per_user',
        'starts_at',
        'expires_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'minimum_order_amount' => 'decimal:2',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    // Relations
    public function usages(): HasMany
    {
        return $this->hasMany(PromoCodeUsage::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    // Méthodes
    public function calculateDiscount(float $orderAmount): float
    {
        if ($this->type === 'percentage') {
            return ($orderAmount * $this->value) / 100;
        }

        return min($this->value, $orderAmount);
    }

    public function isValidForUser(int $userId, float $orderAmount): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->starts_at && now() < $this->starts_at) {
            return false;
        }

        if ($this->expires_at && now() > $this->expires_at) {
            return false;
        }

        if ($orderAmount < $this->minimum_order_amount) {
            return false;
        }

        if ($this->max_uses) {
            $totalUses = $this->usages()->count();
            if ($totalUses >= $this->max_uses) {
                return false;
            }
        }

        $userUses = $this->usages()->where('user_id', $userId)->count();
        if ($userUses >= $this->max_uses_per_user) {
            return false;
        }

        return true;
    }
}
