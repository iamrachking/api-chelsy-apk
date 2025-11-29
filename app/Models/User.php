<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Notifications\ResetPasswordNotification;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'firstname',
        'lastname',
        'email',
        'password',
        'phone',
        'birth_date',
        'gender',
        'avatar',
        'email_verified',
        'verification_token',
        'last_login_at',
        'is_admin',
        'is_blocked',
        'is_driver',
        'fcm_token',
        'fcm_token_updated_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'verification_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'birth_date' => 'date',
            'last_login_at' => 'datetime',
            'fcm_token_updated_at' => 'datetime',
            'email_verified' => 'boolean',
            'is_admin' => 'boolean',
            'is_blocked' => 'boolean',
            'is_driver' => 'boolean',
            'password' => 'hashed',
        ];
    }

    // Relations
    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    public function defaultAddress()
    {
        return $this->hasOne(Address::class)->where('is_default', true);
    }

    public function carts()
    {
        return $this->hasMany(Cart::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function complaints()
    {
        return $this->hasMany(Complaint::class);
    }

    public function promoCodeUsages()
    {
        return $this->hasMany(PromoCodeUsage::class);
    }

    public function deliveryPositions()
    {
        return $this->hasMany(DeliveryPosition::class, 'driver_id');
    }

    public function assignedOrders()
    {
        return $this->hasMany(Order::class, 'driver_id');
    }

    // Accessor pour le nom complet
    public function getNameAttribute(): string
    {
        return trim($this->firstname . ' ' . $this->lastname);
    }

    /**
     * Envoyer la notification de réinitialisation de mot de passe.
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
