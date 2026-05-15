<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'sku',
        'name',
        'description',
        'base_price',
        'main_image',
        'status',
    ];

    /**
     * Generate an SKU automatically when a product is created.
     */
    protected static function booted()
    {
        static::creating(function ($product) {
            if (empty($product->sku)) {
                $product->sku = self::generateSku();
            }
        });
    }

    /**
     * Generate SKU unik.
     * Format: PRD-YYYYMMDD-XXXX (contoh: PRD-20251006-0001)
     */
    public static function generateSku()
    {
        $prefix = 'PRD';
        $date   = now()->format('Ymd');
        $lastId = self::max('id') + 1;

        return sprintf("%s-%s-%04d", $prefix, $date, $lastId);
    }

    /**
     * Product variants that belong to this product.
     */
    public function variants()
    {
        // Foreign key: product_id on the product_variants table.
        return $this->hasMany(ProductVariant::class, 'product_id', 'id');
    }

    /**
     * Product images that belong to this product.
     */
    public function images()
    {
        // Foreign key: product_id on the product_images table.
        return $this->hasMany(ProductImage::class, 'product_id', 'id');
    }

    public function availableImages()
    {
        $images = $this->relationLoaded('images') ? $this->images : $this->images()->get();

        return $images->filter(fn (ProductImage $image) => $image->existsOnPublicDisk())->values();
    }

    public function firstAvailableImage(): ?ProductImage
    {
        return $this->availableImages()->first();
    }

    /**
     * Orders that include this product.
     */
    public function orders()
    {
        // Foreign key: product_id on the orders table.
        return $this->hasMany(Order::class, 'product_id', 'id');
    }

    /**
     * Get the total stock of all variants.
     */
    public function totalStock(): int
    {
        if ($this->relationLoaded('variants')) {
            return $this->variants->sum('stock');
        }

        return $this->variants()->sum('stock');
    }

    /**
     * Check if the product is available.
     */
    public function isAvailable(): bool
    {
        return $this->totalStock() > 0;
    }

    /**
     * Get the availability label.
     */
    public function availabilityLabel(): string
    {
        return $this->isAvailable() ? 'Tersedia' : 'Saat Ini Disewa';
    }

    /**
     * Get the availability badge class.
     */
    public function availabilityBadgeClass(): string
    {
        return $this->isAvailable() ? 'bg-success' : 'bg-secondary';
    }
}
