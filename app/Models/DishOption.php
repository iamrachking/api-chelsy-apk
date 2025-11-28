<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DishOption extends Model
{
    protected $fillable = [
        'dish_id',
        'name',
        'type',
        'is_required',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
        ];
    }

    // Relations
    public function dish(): BelongsTo
    {
        return $this->belongsTo(Dish::class);
    }

    public function values(): HasMany
    {
        return $this->hasMany(DishOptionValue::class)->orderBy('order');
    }
}
