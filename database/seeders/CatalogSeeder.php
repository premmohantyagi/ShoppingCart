<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Tag;
use App\Models\TaxClass;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        // Categories
        $electronics = Category::create(['name' => 'Electronics', 'sort_order' => 1, 'status' => 'active']);
        Category::create(['name' => 'Mobiles', 'parent_id' => $electronics->id, 'sort_order' => 1, 'status' => 'active']);
        Category::create(['name' => 'Laptops', 'parent_id' => $electronics->id, 'sort_order' => 2, 'status' => 'active']);
        Category::create(['name' => 'Accessories', 'parent_id' => $electronics->id, 'sort_order' => 3, 'status' => 'active']);

        $fashion = Category::create(['name' => 'Fashion', 'sort_order' => 2, 'status' => 'active']);
        Category::create(['name' => 'Men', 'parent_id' => $fashion->id, 'sort_order' => 1, 'status' => 'active']);
        Category::create(['name' => 'Women', 'parent_id' => $fashion->id, 'sort_order' => 2, 'status' => 'active']);

        $home = Category::create(['name' => 'Home & Kitchen', 'sort_order' => 3, 'status' => 'active']);
        Category::create(['name' => 'Furniture', 'parent_id' => $home->id, 'sort_order' => 1, 'status' => 'active']);
        Category::create(['name' => 'Appliances', 'parent_id' => $home->id, 'sort_order' => 2, 'status' => 'active']);

        $sports = Category::create(['name' => 'Sports & Fitness', 'sort_order' => 4, 'status' => 'active']);
        Category::create(['name' => 'Gym Equipment', 'parent_id' => $sports->id, 'sort_order' => 1, 'status' => 'active']);
        Category::create(['name' => 'Sportswear', 'parent_id' => $sports->id, 'sort_order' => 2, 'status' => 'active']);

        $books = Category::create(['name' => 'Books', 'sort_order' => 5, 'status' => 'active']);
        Category::create(['name' => 'Fiction', 'parent_id' => $books->id, 'sort_order' => 1, 'status' => 'active']);
        Category::create(['name' => 'Non-Fiction', 'parent_id' => $books->id, 'sort_order' => 2, 'status' => 'active']);

        $beauty = Category::create(['name' => 'Beauty & Health', 'sort_order' => 6, 'status' => 'active']);
        Category::create(['name' => 'Skincare', 'parent_id' => $beauty->id, 'sort_order' => 1, 'status' => 'active']);
        Category::create(['name' => 'Grooming', 'parent_id' => $beauty->id, 'sort_order' => 2, 'status' => 'active']);

        // Brands
        Brand::create(['name' => 'Samsung', 'status' => 'active']);
        Brand::create(['name' => 'Apple', 'status' => 'active']);
        Brand::create(['name' => 'Nike', 'status' => 'active']);
        Brand::create(['name' => 'Sony', 'status' => 'active']);
        Brand::create(['name' => 'LG', 'status' => 'active']);
        Brand::create(['name' => 'Adidas', 'status' => 'active']);
        Brand::create(['name' => 'Penguin Books', 'status' => 'active']);
        Brand::create(['name' => 'Lakme', 'status' => 'active']);

        // Tags
        Tag::create(['name' => 'Bestseller']);
        Tag::create(['name' => 'New Arrival']);
        Tag::create(['name' => 'On Sale']);
        Tag::create(['name' => 'Trending']);
        Tag::create(['name' => 'Limited Edition']);

        // Attributes
        $size = Attribute::create(['name' => 'Size']);
        $size->values()->createMany([
            ['value' => 'XS', 'sort_order' => 0],
            ['value' => 'S', 'sort_order' => 1],
            ['value' => 'M', 'sort_order' => 2],
            ['value' => 'L', 'sort_order' => 3],
            ['value' => 'XL', 'sort_order' => 4],
            ['value' => 'XXL', 'sort_order' => 5],
        ]);

        $color = Attribute::create(['name' => 'Color']);
        $color->values()->createMany([
            ['value' => 'Red', 'sort_order' => 0],
            ['value' => 'Blue', 'sort_order' => 1],
            ['value' => 'Black', 'sort_order' => 2],
            ['value' => 'White', 'sort_order' => 3],
            ['value' => 'Green', 'sort_order' => 4],
        ]);

        $storage = Attribute::create(['name' => 'Storage']);
        $storage->values()->createMany([
            ['value' => '64GB', 'sort_order' => 0],
            ['value' => '128GB', 'sort_order' => 1],
            ['value' => '256GB', 'sort_order' => 2],
            ['value' => '512GB', 'sort_order' => 3],
            ['value' => '1TB', 'sort_order' => 4],
        ]);

        // Tax Classes
        $standard = TaxClass::create(['name' => 'Standard', 'is_default' => true]);
        $standard->taxes()->create(['name' => 'GST', 'rate' => 18.00, 'is_active' => true]);

        $reduced = TaxClass::create(['name' => 'Reduced', 'is_default' => false]);
        $reduced->taxes()->create(['name' => 'GST Reduced', 'rate' => 5.00, 'is_active' => true]);

        TaxClass::create(['name' => 'Tax Exempt', 'is_default' => false]);

        // Warehouses
        Warehouse::create([
            'name' => 'Main Warehouse',
            'location' => 'Mumbai',
            'address' => '123 Warehouse Road, Mumbai, Maharashtra 400001',
            'is_primary' => true,
            'status' => 'active',
        ]);

        Warehouse::create([
            'name' => 'North Hub',
            'location' => 'Delhi',
            'address' => '456 Logistics Park, New Delhi 110001',
            'is_primary' => false,
            'status' => 'active',
        ]);
    }
}
