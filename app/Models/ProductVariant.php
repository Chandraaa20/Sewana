<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
  use HasFactory;

  protected $fillable = [
    'product_id',
    'size',
    'color',
    'price',
    'deposit',
    'late_fee',
    'stock',
    'status',
    'notes',
  ];

  /**
   * Parent product for this variant.
   */
  public function product()
  {
    return $this->belongsTo(Product::class);
  }

  /**
   * Orders that use this variant.
   */
  public function orders()
  {
    return $this->hasMany(Order::class, 'variant_id');
  }

  /**
   * Get availability label based on stock.
   */
  public function availabilityLabel(): string
  {
    if ($this->status !== 'tersedia') {
      return match ($this->status) {
        'disewa' => 'Sedang Disewa',
        'rusak' => 'Rusak',
        'hilang' => 'Hilang',
        default => 'Tidak Tersedia',
      };
    }

    return $this->stock > 0 ? 'Tersedia' : 'Stok Habis';
  }
}
