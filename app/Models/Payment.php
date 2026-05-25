<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    public const METHOD_CASH = Order::PAYMENT_METHOD_CASH;
    public const METHOD_QRIS = Order::PAYMENT_METHOD_QRIS;

    public const STATUS_PENDING = Order::PAYMENT_STATUS_PENDING;
    public const STATUS_PAID = Order::PAYMENT_STATUS_PAID;
    public const STATUS_FAILED = Order::PAYMENT_STATUS_FAILED;
    public const STATUS_EXPIRED = Order::PAYMENT_STATUS_EXPIRED;

    protected $fillable = [
        'order_id',
        'amount',
        'method',
        'status',
        'proof',
        'paid_at',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
