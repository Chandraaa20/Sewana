<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ProductImage extends Model
{
    protected $fillable = ['product_id', 'image_url'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function publicUrl(): string
    {
        return Storage::disk('public')->url($this->image_url);
    }

    public function existsOnPublicDisk(): bool
    {
        return filled($this->image_url) && Storage::disk('public')->exists($this->image_url);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }
}
