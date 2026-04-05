<?php

namespace Tests\Feature\Inventory;

use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Enums\StockMovementType;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockItem;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    private InventoryService $service;
    private Product $product;
    private Warehouse $warehouse;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->service = app(InventoryService::class);

        $user = User::factory()->create();
        $user->assignRole('vendor');
        $vendor = Vendor::create(['user_id' => $user->id, 'business_name' => 'V', 'status' => 'approved', 'kyc_status' => 'approved']);
        $category = Category::create(['name' => 'C', 'status' => 'active']);

        $this->product = Product::create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
            'name' => 'Test Product',
            'product_type' => ProductType::Simple,
            'regular_price' => 100,
            'status' => ProductStatus::Published,
            'published_at' => now(),
        ]);

        $this->warehouse = Warehouse::create(['name' => 'Main', 'is_primary' => true, 'status' => 'active']);
    }

    public function test_set_stock_creates_stock_item_and_movement(): void
    {
        $stockItem = $this->service->setStock($this->warehouse->id, $this->product->id, null, 50);

        $this->assertEquals(50, $stockItem->in_stock);
        $this->assertEquals(50, $stockItem->opening_stock);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $this->product->id,
            'movement_type' => StockMovementType::Opening->value,
            'quantity' => 50,
        ]);
    }

    public function test_reserve_stock_decrements_and_creates_reservation(): void
    {
        $this->service->setStock($this->warehouse->id, $this->product->id, null, 20);

        $reservation = $this->service->reserveStock($this->product->id, null, 5, 1);

        $this->assertEquals(5, $reservation->quantity);

        $stockItem = StockItem::where('product_id', $this->product->id)->first();
        $this->assertEquals(15, $stockItem->in_stock);
        $this->assertEquals(5, $stockItem->reserved_stock);
    }

    public function test_reserve_stock_fails_with_insufficient_stock(): void
    {
        $this->service->setStock($this->warehouse->id, $this->product->id, null, 3);

        $this->expectException(\RuntimeException::class);
        $this->service->reserveStock($this->product->id, null, 10);
    }

    public function test_release_stock_restores_availability(): void
    {
        $this->service->setStock($this->warehouse->id, $this->product->id, null, 20);
        $reservation = $this->service->reserveStock($this->product->id, null, 5);

        $this->service->releaseStock($reservation);

        $stockItem = StockItem::where('product_id', $this->product->id)->first();
        $this->assertEquals(20, $stockItem->in_stock);
        $this->assertEquals(0, $stockItem->reserved_stock);
    }

    public function test_confirm_sold_converts_reserved_to_sold(): void
    {
        $this->service->setStock($this->warehouse->id, $this->product->id, null, 20);
        $reservation = $this->service->reserveStock($this->product->id, null, 5);

        $this->service->confirmSold($reservation);

        $stockItem = StockItem::where('product_id', $this->product->id)->first();
        $this->assertEquals(15, $stockItem->in_stock);
        $this->assertEquals(0, $stockItem->reserved_stock);
        $this->assertEquals(5, $stockItem->sold_stock);
    }

    public function test_get_available_stock_returns_correct_value(): void
    {
        $this->service->setStock($this->warehouse->id, $this->product->id, null, 20);
        $this->service->reserveStock($this->product->id, null, 3);

        $available = $this->service->getAvailableStock($this->product->id);

        $this->assertEquals(14, $available);
    }
}
