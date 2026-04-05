<?php

namespace Tests\Feature\Api;

use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Vendor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    private Vendor $vendor;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('vendor');
        $this->vendor = Vendor::create([
            'user_id' => $user->id,
            'business_name' => 'Test Vendor',
            'status' => 'approved',
            'kyc_status' => 'approved',
        ]);
        $this->category = Category::create(['name' => 'Test Category', 'status' => 'active']);
    }

    public function test_products_index_returns_published_products(): void
    {
        Product::create([
            'vendor_id' => $this->vendor->id,
            'category_id' => $this->category->id,
            'name' => 'Published Product',
            'product_type' => ProductType::Simple,
            'regular_price' => 100,
            'status' => ProductStatus::Published,
            'published_at' => now(),
        ]);

        Product::create([
            'vendor_id' => $this->vendor->id,
            'category_id' => $this->category->id,
            'name' => 'Draft Product',
            'product_type' => ProductType::Simple,
            'regular_price' => 50,
            'status' => ProductStatus::Draft,
        ]);

        $response = $this->getJson('/api/v1/products');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.name', 'Published Product');
    }

    public function test_product_show_by_slug(): void
    {
        $product = Product::create([
            'vendor_id' => $this->vendor->id,
            'category_id' => $this->category->id,
            'name' => 'Slug Test',
            'product_type' => ProductType::Simple,
            'regular_price' => 200,
            'status' => ProductStatus::Published,
            'published_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/products/' . $product->slug);

        $response->assertOk()->assertJsonPath('data.name', 'Slug Test');
    }

    public function test_products_can_be_filtered_by_category(): void
    {
        $cat2 = Category::create(['name' => 'Other Category', 'status' => 'active']);

        Product::create([
            'vendor_id' => $this->vendor->id,
            'category_id' => $this->category->id,
            'name' => 'Cat 1 Product',
            'product_type' => ProductType::Simple,
            'regular_price' => 100,
            'status' => ProductStatus::Published,
            'published_at' => now(),
        ]);

        Product::create([
            'vendor_id' => $this->vendor->id,
            'category_id' => $cat2->id,
            'name' => 'Cat 2 Product',
            'product_type' => ProductType::Simple,
            'regular_price' => 100,
            'status' => ProductStatus::Published,
            'published_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/products?category_id=' . $this->category->id);

        $response->assertOk()->assertJsonCount(1, 'data');
    }
}
