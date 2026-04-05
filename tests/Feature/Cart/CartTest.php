<?php

namespace Tests\Feature\Cart;

use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockItem;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->customer = User::factory()->create(['status' => 'active']);
        $this->customer->assignRole('customer');

        $vendorUser = User::factory()->create();
        $vendorUser->assignRole('vendor');
        $vendor = Vendor::create([
            'user_id' => $vendorUser->id,
            'business_name' => 'Test Vendor',
            'status' => 'approved',
            'kyc_status' => 'approved',
        ]);

        $category = Category::create(['name' => 'Test', 'status' => 'active']);

        $this->product = Product::create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'name' => 'Test Product',
            'product_type' => ProductType::Simple,
            'regular_price' => 500,
            'status' => ProductStatus::Published,
            'published_at' => now(),
        ]);

        $warehouse = Warehouse::create(['name' => 'Main', 'is_primary' => true, 'status' => 'active']);
        StockItem::create([
            'warehouse_id' => $warehouse->id,
            'product_id' => $this->product->id,
            'in_stock' => 10,
            'low_stock_threshold' => 2,
        ]);
    }

    public function test_authenticated_user_can_add_to_cart(): void
    {
        $token = $this->customer->createToken('test')->plainTextToken;

        $response = $this->postJson('/api/v1/cart/add', [
            'product_id' => $this->product->id,
            'quantity' => 2,
        ], ['Authorization' => 'Bearer ' . $token]);

        $response->assertOk()->assertJsonPath('success', true);
        $response->assertJsonPath('cart.item_count', 2);
    }

    public function test_cannot_add_more_than_available_stock(): void
    {
        $token = $this->customer->createToken('test')->plainTextToken;

        $response = $this->postJson('/api/v1/cart/add', [
            'product_id' => $this->product->id,
            'quantity' => 20,
        ], ['Authorization' => 'Bearer ' . $token]);

        $response->assertStatus(422)->assertJsonPath('success', false);
    }

    public function test_cart_can_be_viewed(): void
    {
        $token = $this->customer->createToken('test')->plainTextToken;

        // Add item first
        $this->postJson('/api/v1/cart/add', [
            'product_id' => $this->product->id,
            'quantity' => 1,
        ], ['Authorization' => 'Bearer ' . $token]);

        $response = $this->getJson('/api/v1/cart', ['Authorization' => 'Bearer ' . $token]);

        $response->assertOk()->assertJsonPath('data.item_count', 1);
    }

    public function test_cart_item_can_be_removed(): void
    {
        $token = $this->customer->createToken('test')->plainTextToken;

        // Add item
        $this->postJson('/api/v1/cart/add', [
            'product_id' => $this->product->id,
            'quantity' => 1,
        ], ['Authorization' => 'Bearer ' . $token]);

        $cart = $this->customer->fresh();
        $cartItem = \App\Models\CartItem::first();

        $response = $this->postJson('/api/v1/cart/remove', [
            'cart_item_id' => $cartItem->id,
        ], ['Authorization' => 'Bearer ' . $token]);

        $response->assertOk()->assertJsonPath('success', true);
        $response->assertJsonPath('cart.item_count', 0);
    }
}
