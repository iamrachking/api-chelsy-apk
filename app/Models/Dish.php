<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dish extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'price',
        'discount_price',
        'images',
        'preparation_time_minutes',
        'allergens',
        'nutritional_info',
        'is_available',
        'is_featured',
        'is_new',
        'is_vegetarian',
        'is_specialty',
        'order_count',
        'average_rating',
        'review_count',
    ];

    protected function casts(): array
    {
        return [
            'images' => 'array',
            'allergens' => 'array',
            'nutritional_info' => 'array',
            'price' => 'decimal:2',
            'discount_price' => 'decimal:2',
            'average_rating' => 'decimal:2',
            'is_available' => 'boolean',
            'is_featured' => 'boolean',
            'is_new' => 'boolean',
            'is_vegetarian' => 'boolean',
            'is_specialty' => 'boolean',
        ];
    }

    // Relations
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(DishOption::class)->orderBy('order');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class)->where('type', 'dish');
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    // Accessors
    public function getFinalPriceAttribute(): float
    {
        return $this->discount_price ?? $this->price;
    }

    public function getHasDiscountAttribute(): bool
    {
        return $this->discount_price !== null && $this->discount_price < $this->price;
    }

    // Accessor pour obtenir la première image (compatibilité avec le code existant)
    public function getImageAttribute(): ?string
    {
        $images = $this->images ?? [];
        return !empty($images) ? $images[0] : null;
    }

    // Mutator pour définir l'image (compatibilité avec le code existant)
    public function setImageAttribute(?string $value): void
    {
        if ($value) {
            $this->attributes['images'] = json_encode([$value]);
        }
    }
}
