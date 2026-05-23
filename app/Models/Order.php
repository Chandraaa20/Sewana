<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'customer_name',
        'identity_photo',
        'product_id',
        'variant_id',
        'start_date',
        'end_date',
        'source',
        'rent_days',
        'price_per_day',
        'total_price',
        'order_status',
        'payment_method',
        'payment_status',
        'payment_reference',
        'payment_gateway',
        'payment_payload',
        'validation_token',
        'paid_at',
        'address',
        'bukti_pembayaran'
    ];

    protected $casts = [
        'payment_payload' => 'array',
        'paid_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            if (filled($order->validation_token)) {
                return;
            }

            $order->validation_token = self::generateValidationToken();
        });
    }

    private static function generateValidationToken(): string
    {
        do {
            $token = Str::random(64);
        } while (self::query()->where('validation_token', $token)->exists());

        return $token;
    }

    /**
     * User that owns this order.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Product assigned to this order.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Product variant assigned to this order.
     */
    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    /**
     * Payment associated with this order.
     */
    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    public function identityPhotoUrl(): ?string
    {
        return $this->publicDiskUrl($this->identity_photo);
    }

    public function paymentProofUrl(): ?string
    {
        return $this->publicDiskUrl($this->bukti_pembayaran);
    }

    private function publicDiskUrl(?string $path): ?string
    {
        if (! filled($path) || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }
}
