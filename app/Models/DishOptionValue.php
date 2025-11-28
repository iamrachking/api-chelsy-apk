<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DishOptionValue extends Model
{
    protected $fillable = [
        'dish_option_id',
        'value',
        'price_modifier',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'price_modifier' => 'decimal:2',
        ];
    }

    // Relations
    public function option(): BelongsTo
    {
        return $this->belongsTo(DishOption::class);
    }
}
