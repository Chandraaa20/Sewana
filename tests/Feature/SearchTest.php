<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_owner_can_search_products_by_name_description_or_sku(): void
    {
        $owner = User::factory()->create();
        $owner->syncRoles('pemilik');

        Product::factory()->create([
            'name' => 'Kebaya Anggun Mawar',
            'sku' => 'SWN-MAWAR',
            'description' => 'Busana pesta keluarga',
            'status' => 'active',
        ]);

        Product::factory()->create([
            'name' => 'Jas Formal Malam',
            'sku' => 'SWN-JAS',
            'description' => 'Setelan formal pria',
            'status' => 'active',
        ]);

        $this->actingAs($owner)
            ->get(route('pemilik.products.index', ['search' => 'mawar']))
            ->assertOk()
            ->assertSee('Kebaya Anggun Mawar')
            ->assertDontSee('Jas Formal Malam');
    }

    public function test_customer_product_search_keeps_availability_filter(): void
    {
        $customer = User::factory()->create();

        $availableProduct = Product::factory()->create([
            'name' => 'Dress Lamaran Melati',
            'status' => 'active',
        ]);
        ProductVariant::factory()->for($availableProduct)->create(['stock' => 2]);

        $inactiveProduct = Product::factory()->create([
            'name' => 'Dress Lamaran Nonaktif',
            'status' => 'inactive',
        ]);
        ProductVariant::factory()->for($inactiveProduct)->create(['stock' => 2]);

        $this->actingAs($customer)
            ->get(route('penyewa.products.index', ['search' => 'lamaran']))
            ->assertOk()
            ->assertSee('Dress Lamaran Melati')
            ->assertDontSee('Dress Lamaran Nonaktif');
    }

    public function test_owner_can_search_users_by_name_or_email(): void
    {
        $owner = User::factory()->create();
        $owner->syncRoles('pemilik');

        User::factory()->create([
            'name' => 'Rani Pencarian',
            'email' => 'rani-search@example.test',
        ]);

        User::factory()->create([
            'name' => 'Budi Tidak Cocok',
            'email' => 'budi@example.test',
        ]);

        $this->actingAs($owner)
            ->get(route('pemilik.users.index', ['search' => 'rani-search']))
            ->assertOk()
            ->assertSee('Rani Pencarian')
            ->assertDontSee('Budi Tidak Cocok');
    }

    public function test_staff_can_search_orders_by_customer_product_sku_or_id(): void
    {
        $staff = User::factory()->create();
        $staff->syncRoles('pegawai');

        $product = Product::factory()->create([
            'name' => 'Beskap Satria',
            'sku' => 'SWN-SATRIA',
            'status' => 'active',
        ]);
        $variant = ProductVariant::factory()->for($product)->create(['stock' => 1]);

        $otherProduct = Product::factory()->create([
            'name' => 'Kebaya Lain',
            'sku' => 'SWN-LAIN',
            'status' => 'active',
        ]);
        $otherVariant = ProductVariant::factory()->for($otherProduct)->create(['stock' => 1]);

        Order::create([
            'user_id' => $staff->id,
            'customer_name' => 'Dewi Searchable',
            'identity_photo' => 'identity_photos/dewi.jpg',
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'source' => 'offline',
            'rent_days' => 2,
            'price_per_day' => 100000,
            'total_price' => 200000,
            'order_status' => 'pending',
            'payment_status' => 'pending',
            'address' => 'Bandung',
        ]);

        Order::create([
            'user_id' => $staff->id,
            'customer_name' => 'Andi Tidak Cocok',
            'identity_photo' => 'identity_photos/andi.jpg',
            'product_id' => $otherProduct->id,
            'variant_id' => $otherVariant->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
            'source' => 'offline',
            'rent_days' => 2,
            'price_per_day' => 100000,
            'total_price' => 200000,
            'order_status' => 'pending',
            'payment_status' => 'pending',
            'address' => 'Bandung',
        ]);

        $this->actingAs($staff)
            ->get(route('pegawai.orders.index', ['search' => 'SWN-SATRIA']))
            ->assertOk()
            ->assertSee('Dewi Searchable')
            ->assertDontSee('Andi Tidak Cocok');
    }
}
