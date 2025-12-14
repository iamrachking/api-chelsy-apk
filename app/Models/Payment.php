<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'method',
        'status',
        'transaction_id',
        'mobile_money_provider',
        'mobile_money_number',
        'amount',
        'payment_data',
        'failure_reason',
    ];

    protected $casts = [
        'payment_data' => 'array',
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['status_label', 'method_label'];

    // Relations
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    // Accessors
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => 'En attente',
            'completed' => 'Complété',
            'failed' => 'Échoué',
            'refunded' => 'Remboursé',
            default => 'Inconnu',
        };
    }

    public function getMethodLabelAttribute(): string
    {
        return match($this->method) {
            'card' => 'Carte bancaire',
            'mobile_money' => 'Mobile Money',
            'cash' => 'Espèces',
            default => 'Autre',
        };
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    // Methods
    public function markAsCompleted(): bool
    {
        return $this->update([
            'status' => 'completed',
        ]);
    }

    public function markAsFailed(string $failureReason = null): bool
    {
        return $this->update([
            'status' => 'failed',
            'failure_reason' => $failureReason,
        ]);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }
}