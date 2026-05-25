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
        $path = $this->publicDiskPath();

        return $path === '' ? '' : asset('storage/' . $path);
    }

    public function existsOnPublicDisk(): bool
    {
        $path = $this->publicDiskPath();

        return $path !== '' && Storage::disk('public')->exists($path);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    private function publicDiskPath(): string
    {
        $path = trim((string) $this->image_url);

        if ($path === '') {
            return '';
        }

        if (preg_match('#^https?://#i', $path)) {
            $path = parse_url($path, PHP_URL_PATH) ?: '';
        }

        $path = ltrim($path, '/');

        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, strlen('storage/'));
        }

        return ltrim($path, '/');
    }
}
