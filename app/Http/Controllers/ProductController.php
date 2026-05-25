<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * Show the product list for admin and staff.
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));

        $products = Product::with(['variants', 'images'])
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->paginate(8)
            ->withQueryString();

        return view('products.index', compact('products'));
    }

    /**
     * Show the product creation form.
     */
    public function create()
    {
        return view('products.create');
    }

    /**
     * Store a new product.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'base_price' => 'nullable|numeric|min:0',
            'status' => 'required|in:active,inactive',

            // Images
            'images' => 'nullable|array|max:10',
            'images.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240',

            // Variants
            'variants' => 'required|array|min:1',
            'variants.*.size' => 'nullable|string|max:10',
            'variants.*.color' => 'nullable|string|max:50',
            'variants.*.price' => 'required|numeric|min:0',
            'variants.*.stock' => 'required|integer|min:0',
            'variants.*.status' => 'required|in:tersedia,disewa,rusak,hilang',
        ]);

        DB::transaction(function () use ($request) {
            $product = Product::create([
                'name' => $request->name,
                'description' => $request->description,
                'base_price' => $request->base_price ?? null,
                'status' => $request->status,
            ]);

            // Store images.
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $file) {
                    $path = $file->store('products', 'public');
                    $product->images()->create(['image_url' => $path]);
                }
            }

            // Store variants.
            foreach ($request->variants as $v) {
                ProductVariant::create([
                    'product_id' => $product->id,
                    'size' => strtoupper($v['size'] ?? ''),
                    'color' => $v['color'] ?? null,
                    'price' => $v['price'],
                    'stock' => $v['stock'],
                    'status' => $v['status'],
                ]);
            }
        });

        return $this->redirectToIndex('Produk berhasil ditambahkan!');
    }

    /**
     * Show the product edit form.
     */
    public function edit(Product $product)
    {
        $product->load(['variants', 'images']);

        return view('products.edit', compact('product'));
    }

    /**
     * Safely handle owner product detail route.
     */
    public function show(Product $product)
    {
        return redirect()->route('pemilik.products.index', [
            'search' => $product->name,
        ]);
    }

    /**
     * Update a product.
     */
    public function update(Request $request, Product $product)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'base_price' => 'nullable|numeric|min:0',
            'status' => 'required|in:active,inactive',

            // Images
            'images' => 'nullable|array|max:10',
            'images.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240',

            // Variants
            'variants' => 'required|array|min:1',
            'variants.*.size' => 'nullable|string|max:10',
            'variants.*.color' => 'nullable|string|max:50',
            'variants.*.price' => 'required|numeric|min:0',
            'variants.*.stock' => 'required|integer|min:0',
            'variants.*.status' => 'required|in:tersedia,disewa,rusak,hilang',
        ]);

        DB::transaction(function () use ($request, $product) {
            $product->update([
                'name' => $request->name,
                'description' => $request->description,
                'base_price' => $request->base_price ?? null,
                'status' => $request->status,
            ]);

            // Delete marked images.
            if ($request->delete_images) {
                foreach ($request->delete_images as $id) {
                    $img = $product->images()->find($id);
                    if ($img) {
                        Storage::disk('public')->delete($img->image_url);
                        $img->delete();
                    }
                }
            }

            // Upload new images.
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $file) {
                    $path = $file->store('products', 'public');
                    $product->images()->create(['image_url' => $path]);
                }
            }

            // UPDATE VARIANTS YANG ADA, JANGAN DELETE SEMUA
            $newVariantIds = [];
            foreach ($request->variants as $v) {
                $variantId = $v['id'] ?? null;

                if ($variantId && $product->variants()->where('id', $variantId)->exists()) {
                    // Update existing variant
                    $product->variants()->where('id', $variantId)->update([
                        'size' => strtoupper($v['size'] ?? ''),
                        'color' => $v['color'] ?? null,
                        'price' => $v['price'],
                        'stock' => $v['stock'],
                        'status' => $v['status'],
                    ]);
                    $newVariantIds[] = $variantId;
                } else {
                    // Create new variant
                    $newVar = ProductVariant::create([
                        'product_id' => $product->id,
                        'size' => strtoupper($v['size'] ?? ''),
                        'color' => $v['color'] ?? null,
                        'price' => $v['price'],
                        'stock' => $v['stock'],
                        'status' => $v['status'],
                    ]);
                    $newVariantIds[] = $newVar->id;
                }
            }

            // Delete only variants that are NOT in newVariantIds AND have no active orders
            $variantsToDelete = $product->variants()
                ->whereNotIn('id', $newVariantIds)
                ->whereDoesntHave('orders', function ($q) {
                    $q->whereIn('order_status', ['pending', 'approved', 'rented']);
                })
                ->get();

            foreach ($variantsToDelete as $variant) {
                $variant->delete();
            }
        });

        return $this->redirectToIndex('Produk berhasil diperbarui!');
    }

    /**
     * Delete a product.
     */
    public function destroy(Product $product)
    {
        $hasProductOrders = $product->orders()->exists();
        $hasVariantOrders = $product->variants()->whereHas('orders')->exists();

        if ($hasProductOrders || $hasVariantOrders) {
            return back()->with('error', 'Produk tidak bisa dihapus karena sudah memiliki riwayat pesanan.');
        }

        DB::transaction(function () use ($product) {
            foreach ($product->images as $img) {
                Storage::disk('public')->delete($img->image_url);
                $img->delete();
            }

            $product->variants()->delete();
            $product->delete();
        });

        return $this->redirectToIndex('Produk berhasil dihapus!');
    }

    /**
     * Show products for customers.
     */
    public function listForCustomer(Request $request)
    {
        $search = trim((string) $request->input('search', ''));

        $products = Product::with(['images', 'variants'])
            ->where('status', 'active')
            ->whereHas('variants', function ($q) {
                $q->where('stock', '>', 0)
                    ->where('status', 'tersedia');
            })
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->paginate(8)
            ->withQueryString();

        return view('products.customer', compact('products'));
    }

    /**
     * Redirect to the role-specific index route.
     */
    /**
     * @var \App\Models\User|\Spatie\Permission\Traits\HasRoles
     */
    private function redirectToIndex(string $message)
    {
        $user = Auth::user();

        if ($user->hasRole('pegawai')) {
            $route = 'pegawai.products.index';
        } elseif ($user->hasRole('pemilik')) {
            $route = 'pemilik.products.index';
        } else {
            $route = 'penyewa.products.index';
        }

        return redirect()->route($route)->with('success', $message);
    }
}
