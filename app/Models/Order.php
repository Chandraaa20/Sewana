<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

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
        'payment_status',
        'address',
        'bukti_pembayaran'
    ];

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
