<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    public const ORDER_STATUS_PENDING = 'pending';
    public const ORDER_STATUS_APPROVED = 'approved';
    public const ORDER_STATUS_RENTED = 'rented';
    public const ORDER_STATUS_RETURNED = 'returned';
    public const ORDER_STATUS_CANCELLED = 'cancelled';
    public const ORDER_STATUS_REJECTED = 'rejected';
    public const ORDER_STATUS_REFUNDED = 'refunded';

    public const ORDER_STATUSES = [
        self::ORDER_STATUS_PENDING,
        self::ORDER_STATUS_APPROVED,
        self::ORDER_STATUS_RENTED,
        self::ORDER_STATUS_RETURNED,
        self::ORDER_STATUS_CANCELLED,
    ];

    public const INVALID_TRANSACTION_STATUSES = [
        self::ORDER_STATUS_CANCELLED,
        self::ORDER_STATUS_REJECTED,
        self::ORDER_STATUS_REFUNDED,
    ];

    public const ACTIVE_ORDER_STATUSES = [
        self::ORDER_STATUS_PENDING,
        self::ORDER_STATUS_APPROVED,
        self::ORDER_STATUS_RENTED,
    ];

    public const CLOSED_ORDER_STATUSES = [
        self::ORDER_STATUS_RETURNED,
        self::ORDER_STATUS_CANCELLED,
        self::ORDER_STATUS_REJECTED,
        self::ORDER_STATUS_REFUNDED,
    ];

    public const PAYMENT_STATUS_PENDING = 'pending';
    public const PAYMENT_STATUS_PAID = 'paid';
    public const PAYMENT_STATUS_FAILED = 'failed';
    public const PAYMENT_STATUS_EXPIRED = 'expired';

    public const PAYMENT_STATUSES = [
        self::PAYMENT_STATUS_PENDING,
        self::PAYMENT_STATUS_PAID,
        self::PAYMENT_STATUS_FAILED,
        self::PAYMENT_STATUS_EXPIRED,
    ];

    public const PAYMENT_METHOD_CASH = 'cash';
    public const PAYMENT_METHOD_QRIS = 'qris';

    public const PAYMENT_METHODS = [
        self::PAYMENT_METHOD_CASH,
        self::PAYMENT_METHOD_QRIS,
    ];

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
        'amount_received',
        'change_amount',
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
        'amount_received' => 'integer',
        'change_amount' => 'integer',
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

    public function scopeValidTransaction($query)
    {
        return $query->whereNotIn('order_status', self::INVALID_TRANSACTION_STATUSES);
    }

    public function scopeRevenueEligible($query)
    {
        return $query
            ->validTransaction()
            ->where('payment_status', self::PAYMENT_STATUS_PAID);
    }

    public function scopeActiveRental($query)
    {
        return $query->whereIn('order_status', self::ACTIVE_ORDER_STATUSES);
    }

    public function scopeClosed($query)
    {
        return $query->whereIn('order_status', self::CLOSED_ORDER_STATUSES);
    }

    public function scopeFulfilled($query)
    {
        return $query->where('order_status', self::ORDER_STATUS_RETURNED);
    }

    public function scopeOverlappingPeriod($query, $startDate, $endDate)
    {
        return $query
            ->where('start_date', '<=', $endDate)
            ->where('end_date', '>=', $startDate);
    }

    public function scopeStockHolding($query)
    {
        return $query->where(function ($statusQuery) {
            $statusQuery
                ->whereIn('order_status', [
                    self::ORDER_STATUS_APPROVED,
                    self::ORDER_STATUS_RENTED,
                ])
                ->orWhere(function ($paidPendingQuery) {
                    $paidPendingQuery
                        ->where('order_status', self::ORDER_STATUS_PENDING)
                        ->where('payment_status', self::PAYMENT_STATUS_PAID);
                });
        });
    }

    public function scopePendingPaidHolding($query)
    {
        return $query
            ->where('order_status', self::ORDER_STATUS_PENDING)
            ->where('payment_status', self::PAYMENT_STATUS_PAID);
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
