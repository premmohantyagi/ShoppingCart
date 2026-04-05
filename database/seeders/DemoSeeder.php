<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Models\Address;
use App\Models\Banner;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Page;
use App\Models\Product;
use App\Models\Review;
use App\Models\StockItem;
use App\Models\Tag;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorBankAccount;
use App\Models\VendorWallet;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding demo users...');
        [$customer, $customer2, $vendor1, $vendor2] = $this->seedUsers();

        $this->command->info('Downloading category images...');
        $this->seedCategoryImages();

        $this->command->info('Seeding demo products...');
        $products = $this->seedProducts($vendor1, $vendor2);

        $this->command->info('Downloading product images...');
        $this->seedProductImages($products);

        $this->command->info('Seeding reviews...');
        $this->seedReviews($products, $customer, $customer2);

        $this->command->info('Seeding CMS content...');
        $this->seedPages();
        $this->seedBanners();
        $this->seedBlog();

        $this->command->info('Seeding coupons...');
        $this->seedCoupons();

        $this->command->info('Seeding customer addresses...');
        $this->seedAddresses($customer, $customer2);

        $this->command->info('Demo data seeded successfully!');
    }

    private function seedUsers(): array
    {
        // Customer 1
        $customer = User::create([
            'name' => 'Rahul Sharma',
            'email' => 'customer@shoppingcart.com',
            'password' => Hash::make('password'),
            'phone' => '+91 9876543210',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $customer->assignRole('customer');

        // Customer 2
        $customer2 = User::create([
            'name' => 'Priya Patel',
            'email' => 'priya@shoppingcart.com',
            'password' => Hash::make('password'),
            'phone' => '+91 9876543211',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $customer2->assignRole('customer');

        // Vendor 1 — Electronics
        $vendorUser1 = User::create([
            'name' => 'TechZone India',
            'email' => 'vendor@shoppingcart.com',
            'password' => Hash::make('password'),
            'phone' => '+91 9876500001',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $vendorUser1->assignRole('vendor');

        $vendor1 = Vendor::create([
            'user_id' => $vendorUser1->id,
            'business_name' => 'TechZone India',
            'description' => 'India\'s #1 destination for electronics, mobiles, laptops, and accessories. Authorized reseller with warranty support.',
            'status' => 'approved',
            'commission_type' => 'percentage',
            'commission_value' => 8,
            'kyc_status' => 'approved',
            'approved_at' => now()->subDays(30),
        ]);
        VendorWallet::create(['vendor_id' => $vendor1->id, 'balance' => 15000]);
        VendorBankAccount::create([
            'vendor_id' => $vendor1->id,
            'bank_name' => 'HDFC Bank',
            'account_holder_name' => 'TechZone India Pvt Ltd',
            'account_number' => '50100012345678',
            'ifsc_code' => 'HDFC0001234',
            'is_primary' => true,
        ]);

        // Vendor 2 — Fashion & Home
        $vendorUser2 = User::create([
            'name' => 'StyleHub',
            'email' => 'stylehub@shoppingcart.com',
            'password' => Hash::make('password'),
            'phone' => '+91 9876500002',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $vendorUser2->assignRole('vendor');

        $vendor2 = Vendor::create([
            'user_id' => $vendorUser2->id,
            'business_name' => 'StyleHub',
            'description' => 'Premium fashion, footwear, and home decor. Curated collections from top brands.',
            'status' => 'approved',
            'commission_type' => 'percentage',
            'commission_value' => 12,
            'kyc_status' => 'approved',
            'approved_at' => now()->subDays(20),
        ]);
        VendorWallet::create(['vendor_id' => $vendor2->id, 'balance' => 8500]);
        VendorBankAccount::create([
            'vendor_id' => $vendor2->id,
            'bank_name' => 'ICICI Bank',
            'account_holder_name' => 'StyleHub LLP',
            'account_number' => '60200098765432',
            'ifsc_code' => 'ICIC0005678',
            'is_primary' => true,
        ]);

        return [$customer, $customer2, $vendor1, $vendor2];
    }

    private function seedCategoryImages(): void
    {
        $categoryImages = [
            'Electronics'      => 'https://picsum.photos/seed/cat-electronics/400/400',
            'Fashion'          => 'https://picsum.photos/seed/cat-fashion/400/400',
            'Home & Kitchen'   => 'https://picsum.photos/seed/cat-home/400/400',
            'Sports & Fitness' => 'https://picsum.photos/seed/cat-sports/400/400',
            'Books'            => 'https://picsum.photos/seed/cat-books/400/400',
            'Beauty & Health'  => 'https://picsum.photos/seed/cat-beauty/400/400',
        ];

        foreach ($categoryImages as $catName => $url) {
            $category = Category::where('name', $catName)->first();
            if (!$category) continue;

            try {
                $response = Http::timeout(10)->get($url);
                if ($response->successful()) {
                    $filename = 'category-' . $category->slug . '.jpg';
                    $path = 'categories/' . $filename;
                    Storage::disk('public')->put($path, $response->body());
                    $category->update(['image' => $path]);
                    $this->command->getOutput()->write('.');
                }
            } catch (\Exception $e) {
                $this->command->getOutput()->write('x');
            }
        }
        $this->command->newLine();
    }

    private function seedProducts(Vendor $vendor1, Vendor $vendor2): array
    {
        $warehouse = Warehouse::where('is_primary', true)->first();
        $tags = Tag::all()->keyBy('name');

        $allProducts = [];

        // ── Electronics > Mobiles ──
        $mobiles = Category::where('name', 'Mobiles')->first();
        $samsung = Brand::where('name', 'Samsung')->first();
        $apple = Brand::where('name', 'Apple')->first();

        $mobileProducts = [
            [
                'name' => 'Samsung Galaxy S24 Ultra',
                'brand' => $samsung, 'price' => 129999, 'sale' => 109999,
                'short' => 'Ultimate smartphone with Galaxy AI, S Pen, titanium frame, and 200MP camera.',
                'desc' => '<h3>Galaxy AI. Premium Design.</h3><p>Experience the pinnacle of mobile innovation with the Samsung Galaxy S24 Ultra. Featuring a stunning 6.8" Dynamic AMOLED 2X display, the powerful Snapdragon 8 Gen 3 processor, and an incredible 200MP camera system that captures every detail.</p><ul><li>Galaxy AI for smart translations, summaries, and circle-to-search</li><li>Titanium frame for ultimate durability</li><li>Built-in S Pen for productivity</li><li>5000mAh battery with 45W fast charging</li><li>IP68 water and dust resistance</li></ul>',
                'featured' => true, 'tags' => ['Bestseller', 'Trending'],
                'img_seed' => 1,
            ],
            [
                'name' => 'Apple iPhone 15 Pro Max',
                'brand' => $apple, 'price' => 159900, 'sale' => null,
                'short' => 'Pro. Beyond. A17 Pro chip. 48MP camera. Titanium design.',
                'desc' => '<h3>iPhone 15 Pro Max</h3><p>Forged in titanium — the lightest Pro model ever. With the A17 Pro chip for groundbreaking performance, a 48MP main camera with 5x optical zoom, and all-day battery life.</p><ul><li>A17 Pro chip with GPU 6-core</li><li>48MP Main + 12MP Ultra Wide + 12MP 5x Telephoto</li><li>USB-C with USB 3 speeds</li><li>Action button for quick access</li><li>Up to 29 hours video playback</li></ul>',
                'featured' => true, 'tags' => ['Bestseller', 'New Arrival'],
                'img_seed' => 2,
            ],
            [
                'name' => 'Samsung Galaxy A55 5G',
                'brand' => $samsung, 'price' => 39999, 'sale' => 32999,
                'short' => 'Awesome 5G smartphone with Super AMOLED display and 50MP camera.',
                'desc' => '<p>The Galaxy A55 delivers flagship-level features at an incredible price. Featuring a 6.6" Super AMOLED display, Exynos 1480 processor, and a versatile 50MP triple camera setup.</p>',
                'featured' => false, 'tags' => ['On Sale'],
                'img_seed' => 3,
            ],
            [
                'name' => 'Apple iPhone 15',
                'brand' => $apple, 'price' => 79900, 'sale' => 74900,
                'short' => 'Dynamic Island. 48MP camera. A16 Bionic chip. USB-C.',
                'desc' => '<p>iPhone 15 features Dynamic Island, a 48MP Main camera, and USB-C — all powered by the A16 Bionic chip. Available in stunning new colors.</p>',
                'featured' => true, 'tags' => ['Trending'],
                'img_seed' => 4,
            ],
        ];

        foreach ($mobileProducts as $p) {
            $allProducts[] = $this->createProduct($vendor1, $mobiles, $p, $warehouse, $tags);
        }

        // ── Electronics > Laptops ──
        $laptops = Category::where('name', 'Laptops')->first();

        $laptopProducts = [
            [
                'name' => 'Apple MacBook Air M3',
                'brand' => $apple, 'price' => 114900, 'sale' => null,
                'short' => 'Supercharged by M3 chip. Stunningly thin design. Up to 18 hours battery life.',
                'desc' => '<h3>Lean. Mean. M3 Machine.</h3><p>MacBook Air with M3 delivers incredible performance in an impossibly thin design. With a brilliant 13.6" Liquid Retina display, up to 24GB unified memory, and silent fanless design.</p><ul><li>Apple M3 chip with 8-core CPU and 10-core GPU</li><li>Up to 24GB unified memory</li><li>Up to 2TB SSD storage</li><li>1080p FaceTime HD camera</li><li>MagSafe charging + 2 Thunderbolt ports</li></ul>',
                'featured' => true, 'tags' => ['Bestseller', 'New Arrival'],
                'img_seed' => 10,
            ],
            [
                'name' => 'Samsung Galaxy Book4 Pro 360',
                'brand' => $samsung, 'price' => 164990, 'sale' => 139990,
                'short' => '16" Dynamic AMOLED 2X touchscreen, Intel Core Ultra 7, S Pen included.',
                'desc' => '<p>The Galaxy Book4 Pro 360 combines a stunning 16" AMOLED touchscreen with Intel Core Ultra processing for the ultimate 2-in-1 experience. Includes S Pen for creative work.</p>',
                'featured' => false, 'tags' => ['On Sale', 'Trending'],
                'img_seed' => 11,
            ],
            [
                'name' => 'Apple MacBook Pro 14" M3 Pro',
                'brand' => $apple, 'price' => 199900, 'sale' => null,
                'short' => 'M3 Pro chip. 14.2" Liquid Retina XDR display. 18 hours battery.',
                'desc' => '<p>MacBook Pro 14" with M3 Pro delivers exceptional performance for demanding workflows. Features a stunning Liquid Retina XDR display, up to 36GB unified memory, and the most advanced Apple silicon for pros.</p>',
                'featured' => true, 'tags' => ['Bestseller'],
                'img_seed' => 12,
            ],
        ];

        foreach ($laptopProducts as $p) {
            $allProducts[] = $this->createProduct($vendor1, $laptops, $p, $warehouse, $tags);
        }

        // ── Electronics > Accessories ──
        $accessories = Category::where('name', 'Accessories')->first();
        $sony = Brand::where('name', 'Sony')->first();

        $accessoryProducts = [
            [
                'name' => 'Sony WH-1000XM5 Headphones',
                'brand' => $sony, 'price' => 29990, 'sale' => 24990,
                'short' => 'Industry-leading noise canceling. 30hr battery. Crystal clear hands-free calling.',
                'desc' => '<p>The WH-1000XM5 headphones rewrite the rules for noise cancellation with Auto NC Optimizer, crystal clear hands-free calling with 4 beamforming microphones, and up to 30 hours of battery life.</p>',
                'featured' => true, 'tags' => ['Bestseller', 'On Sale'],
                'img_seed' => 20,
            ],
            [
                'name' => 'Apple AirPods Pro 2 (USB-C)',
                'brand' => $apple, 'price' => 24900, 'sale' => null,
                'short' => 'Adaptive Audio. Personalized Spatial Audio. USB-C. MagSafe charging.',
                'desc' => '<p>AirPods Pro 2 feature the Apple H2 chip for smarter noise cancellation, Adaptive Audio that automatically adjusts to your environment, and a new USB-C charging case with precision finding.</p>',
                'featured' => true, 'tags' => ['Bestseller'],
                'img_seed' => 21,
            ],
            [
                'name' => 'Samsung Galaxy Buds3 Pro',
                'brand' => $samsung, 'price' => 17999, 'sale' => 14999,
                'short' => 'Intelligent ANC, Hi-Fi 24bit audio, blade design, IP57 rated.',
                'desc' => '<p>Galaxy Buds3 Pro deliver Hi-Fi 24-bit audio with intelligent ANC, ultra comfortable blade design, and IP57 water resistance. Seamless Galaxy ecosystem integration.</p>',
                'featured' => false, 'tags' => ['On Sale', 'Trending'],
                'img_seed' => 22,
            ],
            [
                'name' => 'Sony WF-1000XM5 Earbuds',
                'brand' => $sony, 'price' => 24990, 'sale' => 19990,
                'short' => 'World\'s smallest and lightest noise canceling earbuds. Hi-Res Audio.',
                'desc' => '<p>The WF-1000XM5 are Sony\'s smallest noise canceling earbuds ever. Featuring the Integrated Processor V2, Dynamic Driver X, and LDAC Hi-Res Audio support.</p>',
                'featured' => false, 'tags' => ['On Sale'],
                'img_seed' => 23,
            ],
        ];

        foreach ($accessoryProducts as $p) {
            $allProducts[] = $this->createProduct($vendor1, $accessories, $p, $warehouse, $tags);
        }

        // ── Fashion > Men ──
        $men = Category::where('name', 'Men')->first();
        $nike = Brand::where('name', 'Nike')->first();

        $menProducts = [
            [
                'name' => 'Nike Air Max 270',
                'brand' => $nike, 'price' => 12995, 'sale' => 9995,
                'short' => 'Max Air unit for all-day comfort. Mesh upper for breathability.',
                'desc' => '<p>The Nike Air Max 270 features Nike\'s biggest heel Air unit yet for a super soft ride. The sleek design and lightweight construction make it perfect for all-day wear.</p>',
                'featured' => true, 'tags' => ['Bestseller', 'On Sale'],
                'img_seed' => 30,
            ],
            [
                'name' => 'Nike Dri-FIT Running T-Shirt',
                'brand' => $nike, 'price' => 2495, 'sale' => 1995,
                'short' => 'Sweat-wicking Dri-FIT technology. Lightweight and breathable.',
                'desc' => '<p>Stay cool and dry with Nike Dri-FIT technology that moves sweat away from your body. The relaxed fit and lightweight fabric keep you comfortable during any workout.</p>',
                'featured' => false, 'tags' => ['On Sale'],
                'img_seed' => 31,
            ],
            [
                'name' => 'Nike Air Force 1 \'07',
                'brand' => $nike, 'price' => 8195, 'sale' => null,
                'short' => 'The iconic sneaker that started it all. Premium leather upper.',
                'desc' => '<p>The radiance lives on in the Nike Air Force 1 \'07. This classic design puts a fresh spin on what you know best: premium leather, bold colors, and the perfect amount of flash.</p>',
                'featured' => true, 'tags' => ['Bestseller', 'Trending'],
                'img_seed' => 32,
            ],
        ];

        foreach ($menProducts as $p) {
            $allProducts[] = $this->createProduct($vendor2, $men, $p, $warehouse, $tags);
        }

        // ── Fashion > Women ──
        $women = Category::where('name', 'Women')->first();

        $womenProducts = [
            [
                'name' => 'Nike Air Max 90 Women\'s',
                'brand' => $nike, 'price' => 11895, 'sale' => 8995,
                'short' => 'Iconic waffle outsole and stitched overlays in classic style.',
                'desc' => '<p>Nothing as icons as the Air Max 90. Featuring the same design lines as the original, with visible Nike Air cushioning and waffle outsole for timeless comfort.</p>',
                'featured' => true, 'tags' => ['On Sale', 'Trending'],
                'img_seed' => 40,
            ],
            [
                'name' => 'Nike Sportswear Essential Hoodie',
                'brand' => $nike, 'price' => 3695, 'sale' => null,
                'short' => 'Soft brushed fleece. Relaxed fit. Kangaroo pocket.',
                'desc' => '<p>The Nike Sportswear Essential Hoodie is made from soft brushed fleece for warmth and comfort. Features a relaxed fit, kangaroo pocket, and iconic Swoosh logo.</p>',
                'featured' => false, 'tags' => ['New Arrival'],
                'img_seed' => 41,
            ],
        ];

        foreach ($womenProducts as $p) {
            $allProducts[] = $this->createProduct($vendor2, $women, $p, $warehouse, $tags);
        }

        // ── Home & Kitchen > Furniture ──
        $furniture = Category::where('name', 'Furniture')->first();
        $lg = Brand::where('name', 'LG')->first();

        $furnitureProducts = [
            [
                'name' => 'Modern Solid Wood Study Desk',
                'brand' => null, 'price' => 12999, 'sale' => 9999,
                'short' => 'Spacious work desk with cable management and drawer storage.',
                'desc' => '<p>This premium solid wood study desk features a spacious work surface, built-in cable management, and two drawers for storage. Perfect for home office or study room.</p>',
                'featured' => true, 'tags' => ['On Sale', 'New Arrival'],
                'img_seed' => 50,
            ],
            [
                'name' => 'Ergonomic Office Chair Pro',
                'brand' => null, 'price' => 18999, 'sale' => 14999,
                'short' => 'Adjustable lumbar support. Breathable mesh. 3D armrests.',
                'desc' => '<p>Designed for long hours of comfortable sitting. Features adjustable lumbar support, breathable mesh back, 3D adjustable armrests, and tilt mechanism.</p>',
                'featured' => false, 'tags' => ['On Sale', 'Bestseller'],
                'img_seed' => 51,
            ],
        ];

        foreach ($furnitureProducts as $p) {
            $allProducts[] = $this->createProduct($vendor2, $furniture, $p, $warehouse, $tags);
        }

        // ── Home & Kitchen > Appliances ──
        $appliances = Category::where('name', 'Appliances')->first();

        $applianceProducts = [
            [
                'name' => 'LG 655L Side-by-Side Refrigerator',
                'brand' => $lg, 'price' => 74990, 'sale' => 62990,
                'short' => 'InstaView Door-in-Door. Linear Cooling. Smart Diagnosis.',
                'desc' => '<p>The LG 655L Side-by-Side Refrigerator with InstaView lets you see inside without opening the door. Linear Cooling maintains freshness, while the door-in-door design provides easy access to frequently used items.</p>',
                'featured' => true, 'tags' => ['On Sale', 'Trending'],
                'img_seed' => 60,
            ],
            [
                'name' => 'LG 9kg Front Load Washing Machine',
                'brand' => $lg, 'price' => 44990, 'sale' => 37990,
                'short' => 'AI Direct Drive. Steam wash. ThinQ WiFi enabled.',
                'desc' => '<p>LG\'s AI Direct Drive technology detects fabric type and optimizes wash motions. Features steam wash for allergen removal, ThinQ WiFi connectivity, and energy-efficient inverter motor.</p>',
                'featured' => false, 'tags' => ['On Sale'],
                'img_seed' => 61,
            ],
        ];

        foreach ($applianceProducts as $p) {
            $allProducts[] = $this->createProduct($vendor2, $appliances, $p, $warehouse, $tags);
        }

        // ── Sports & Fitness ──
        $gymEquipment = Category::where('name', 'Gym Equipment')->first();
        $sportswear = Category::where('name', 'Sportswear')->first();
        $adidas = Brand::where('name', 'Adidas')->first();

        $sportsProducts = [
            [
                'name' => 'Nike Pegasus 41 Running Shoes',
                'brand' => $nike, 'price' => 11895, 'sale' => 9495,
                'short' => 'ReactX foam delivers 13% more energy return. Lightweight and responsive.',
                'desc' => '<p>The Pegasus 41 continues its legacy as the go-to running shoe. ReactX foam technology provides an incredibly responsive ride while reducing carbon footprint by at least 43% compared to Nike React foam.</p>',
                'featured' => true, 'tags' => ['Bestseller', 'On Sale'],
                'img_seed' => 70,
            ],
            [
                'name' => 'Adidas Ultraboost Light',
                'brand' => $adidas, 'price' => 16999, 'sale' => 12999,
                'short' => 'Lightest Ultraboost ever. BOOST midsole. Primeknit+ upper.',
                'desc' => '<p>The lightest Ultraboost ever made. Features a redesigned BOOST midsole that delivers incredible energy return, paired with a Primeknit+ upper that hugs your foot for a perfect fit.</p>',
                'featured' => true, 'tags' => ['On Sale', 'Trending'],
                'img_seed' => 71,
            ],
            [
                'name' => 'Adjustable Dumbbell Set 24kg',
                'brand' => null, 'price' => 8999, 'sale' => 6999,
                'short' => 'Replaces 15 sets of weights. Quick-change mechanism. Space-saving design.',
                'desc' => '<p>This adjustable dumbbell set replaces 15 individual sets of dumbbells. With a quick-turn dial mechanism, you can change weight from 2.5kg to 24kg in seconds. Perfect for home gym setups.</p>',
                'featured' => false, 'tags' => ['On Sale', 'Bestseller'],
                'img_seed' => 72,
            ],
            [
                'name' => 'Adidas Tiro 24 Training Pants',
                'brand' => $adidas, 'price' => 3499, 'sale' => null,
                'short' => 'AEROREADY moisture management. Slim tapered fit. Zip pockets.',
                'desc' => '<p>Designed for training sessions, the Tiro 24 pants feature AEROREADY technology that manages moisture to keep you dry. Slim tapered fit with zip pockets for secure storage.</p>',
                'featured' => false, 'tags' => ['New Arrival'],
                'img_seed' => 73,
            ],
        ];

        foreach ($sportsProducts as $p) {
            $cat = str_contains($p['name'], 'Dumbbell') ? $gymEquipment : $sportswear;
            $allProducts[] = $this->createProduct($vendor2, $cat, $p, $warehouse, $tags);
        }

        // ── Books ──
        $fiction = Category::where('name', 'Fiction')->first();
        $nonFiction = Category::where('name', 'Non-Fiction')->first();
        $penguin = Brand::where('name', 'Penguin Books')->first();

        $bookProducts = [
            [
                'name' => 'Atomic Habits by James Clear',
                'brand' => $penguin, 'price' => 699, 'sale' => 449,
                'short' => 'Tiny changes, remarkable results. The #1 bestseller on building good habits.',
                'desc' => '<p>No matter your goals, Atomic Habits offers a proven framework for improving every day. James Clear reveals practical strategies that will teach you how to form good habits, break bad ones, and master the tiny behaviors that lead to remarkable results.</p>',
                'featured' => true, 'tags' => ['Bestseller', 'Trending'],
                'img_seed' => 80,
            ],
            [
                'name' => 'The Psychology of Money',
                'brand' => $penguin, 'price' => 499, 'sale' => 349,
                'short' => 'Timeless lessons on wealth, greed, and happiness by Morgan Housel.',
                'desc' => '<p>Doing well with money isn\'t necessarily about what you know. It\'s about how you behave. Morgan Housel shares 19 short stories exploring the strange ways people think about money.</p>',
                'featured' => true, 'tags' => ['Bestseller', 'On Sale'],
                'img_seed' => 81,
            ],
            [
                'name' => 'Project Hail Mary by Andy Weir',
                'brand' => $penguin, 'price' => 599, 'sale' => null,
                'short' => 'A lone astronaut must save humanity. From the author of The Martian.',
                'desc' => '<p>Ryland Grace is the sole survivor on a desperate, last-chance mission. If he can\'t figure out the mystery of what\'s threatening Earth, humanity and the earth itself will perish.</p>',
                'featured' => false, 'tags' => ['New Arrival', 'Trending'],
                'img_seed' => 82,
            ],
            [
                'name' => 'Sapiens: A Brief History of Humankind',
                'brand' => $penguin, 'price' => 599, 'sale' => 399,
                'short' => 'Yuval Noah Harari explores the history that shaped our world.',
                'desc' => '<p>From examining the role of Homo sapiens in the global ecosystem to the future of life, Sapiens is a wildly stimulating journey through the most important revolutions in human history.</p>',
                'featured' => false, 'tags' => ['Bestseller'],
                'img_seed' => 83,
            ],
        ];

        foreach ($bookProducts as $p) {
            $cat = str_contains($p['name'], 'Project Hail Mary') ? $fiction : $nonFiction;
            $allProducts[] = $this->createProduct($vendor2, $cat, $p, $warehouse, $tags);
        }

        // ── Beauty & Health ──
        $skincare = Category::where('name', 'Skincare')->first();
        $grooming = Category::where('name', 'Grooming')->first();
        $lakme = Brand::where('name', 'Lakme')->first();

        $beautyProducts = [
            [
                'name' => 'Lakme Vitamin C+ Serum',
                'brand' => $lakme, 'price' => 799, 'sale' => 599,
                'short' => '10X Vitamin C for bright, glowing skin. Lightweight gel formula.',
                'desc' => '<p>Lakme Vitamin C+ Serum with 10X power of Vitamin C brightens skin, reduces dark spots, and gives you a radiant glow. The lightweight gel formula absorbs quickly without leaving any residue.</p>',
                'featured' => true, 'tags' => ['Bestseller', 'On Sale'],
                'img_seed' => 90,
            ],
            [
                'name' => 'Lakme Absolute Skin Dew Moisturizer',
                'brand' => $lakme, 'price' => 650, 'sale' => null,
                'short' => 'Dewy finish. 24hr hydration. SPF 20 PA++ sun protection.',
                'desc' => '<p>Get that coveted dewy skin look with Lakme Absolute Skin Dew Moisturizer. Provides 24-hour hydration with SPF 20 PA++ protection against harmful UV rays.</p>',
                'featured' => false, 'tags' => ['New Arrival'],
                'img_seed' => 91,
            ],
            [
                'name' => 'Premium Beard Grooming Kit',
                'brand' => null, 'price' => 1499, 'sale' => 999,
                'short' => 'Complete kit: beard oil, balm, comb, scissors, and brush.',
                'desc' => '<p>Everything you need for the perfect beard. This premium grooming kit includes natural beard oil, styling balm, wooden comb, stainless steel scissors, and a boar bristle brush — all in a handsome gift box.</p>',
                'featured' => true, 'tags' => ['On Sale', 'Trending'],
                'img_seed' => 92,
            ],
            [
                'name' => 'Lakme 9to5 CC Cream',
                'brand' => $lakme, 'price' => 399, 'sale' => 329,
                'short' => 'Color transform cream. Conceals, moisturizes, and protects.',
                'desc' => '<p>The Lakme 9to5 CC Cream is your all-in-one skin perfector. It conceals imperfections, moisturizes, and provides SPF 30 protection. Available in multiple shades for Indian skin tones.</p>',
                'featured' => false, 'tags' => ['Bestseller', 'On Sale'],
                'img_seed' => 93,
            ],
        ];

        foreach ($beautyProducts as $p) {
            $cat = str_contains($p['name'], 'Beard') || str_contains($p['name'], 'Grooming') ? $grooming : $skincare;
            $allProducts[] = $this->createProduct($vendor2, $cat, $p, $warehouse, $tags);
        }

        return $allProducts;
    }

    private function createProduct(Vendor $vendor, ?Category $category, array $data, ?Warehouse $warehouse, $tags): Product
    {
        $product = Product::create([
            'vendor_id' => $vendor->id,
            'category_id' => $category?->id,
            'brand_id' => $data['brand']?->id,
            'name' => $data['name'],
            'short_description' => $data['short'],
            'description' => $data['desc'],
            'product_type' => ProductType::Simple,
            'regular_price' => $data['price'],
            'sale_price' => $data['sale'],
            'is_featured' => $data['featured'],
            'status' => ProductStatus::Published,
            'published_at' => now()->subDays(rand(1, 30)),
        ]);

        // Attach tags
        $tagIds = collect($data['tags'] ?? [])
            ->map(fn ($name) => $tags->get($name)?->id)
            ->filter()
            ->toArray();
        if ($tagIds) {
            $product->tags()->sync($tagIds);
        }

        // Stock
        if ($warehouse) {
            $stock = rand(20, 100);
            StockItem::create([
                'warehouse_id' => $warehouse->id,
                'product_id' => $product->id,
                'opening_stock' => $stock,
                'in_stock' => $stock,
                'low_stock_threshold' => 5,
            ]);
        }

        return $product;
    }

    private function seedProductImages(array $products): void
    {
        // Create placeholder images directory
        $imageDir = storage_path('app/public/demo-images');
        if (!File::isDirectory($imageDir)) {
            File::makeDirectory($imageDir, 0755, true);
        }

        foreach ($products as $index => $product) {
            try {
                // Download a unique image from picsum.photos for each product
                $seed = $index + 100;
                $imageUrl = "https://picsum.photos/seed/product{$seed}/600/600";

                $response = Http::timeout(10)->get($imageUrl);

                if ($response->successful()) {
                    $filename = "product-{$product->id}.jpg";
                    $path = "demo-images/{$filename}";
                    Storage::disk('public')->put($path, $response->body());

                    // Add to Spatie MediaLibrary
                    $product->addMedia(storage_path("app/public/{$path}"))
                        ->preservingOriginal()
                        ->toMediaCollection('thumbnail');

                    $this->command->getOutput()->write('.');
                }
            } catch (\Exception $e) {
                // Skip if image download fails — product card has fallback SVG
                $this->command->getOutput()->write('x');
            }
        }
        $this->command->newLine();
    }

    private function seedReviews(array $products, User $customer1, User $customer2): void
    {
        $reviewData = [
            ['rating' => 5, 'title' => 'Absolutely amazing!', 'comment' => 'Best purchase I\'ve made this year. The quality is outstanding and it works exactly as described. Highly recommend to everyone!'],
            ['rating' => 4, 'title' => 'Great product', 'comment' => 'Very happy with this purchase. Great value for money. Minor issue with packaging but the product itself is excellent.'],
            ['rating' => 5, 'title' => 'Worth every rupee', 'comment' => 'Premium quality at a reasonable price. Fast delivery and well-packaged. Would definitely buy from this seller again.'],
            ['rating' => 3, 'title' => 'Decent but expected more', 'comment' => 'The product is okay for the price. It does what it\'s supposed to do but I was expecting slightly better quality based on the description.'],
            ['rating' => 5, 'title' => 'Exceeded expectations', 'comment' => 'I was skeptical at first but this product blew me away. The build quality, performance, and design are all top-notch. 5 stars!'],
            ['rating' => 4, 'title' => 'Good buy', 'comment' => 'Solid product with good build quality. Delivery was quick and customer service was helpful. Would recommend.'],
        ];

        $users = [$customer1, $customer2];

        foreach ($products as $i => $product) {
            // Add 2-3 reviews per product
            $numReviews = rand(2, 3);
            for ($j = 0; $j < $numReviews; $j++) {
                $review = $reviewData[($i + $j) % count($reviewData)];
                $user = $users[($i + $j) % count($users)];

                // Skip if user already reviewed this product
                if (Review::where('product_id', $product->id)->where('user_id', $user->id)->exists()) {
                    continue;
                }

                Review::create([
                    'product_id' => $product->id,
                    'user_id' => $user->id,
                    'rating' => $review['rating'],
                    'title' => $review['title'],
                    'comment' => $review['comment'],
                    'is_verified_purchase' => (bool) rand(0, 1),
                    'status' => 'approved',
                    'created_at' => now()->subDays(rand(1, 30)),
                ]);
            }
        }
    }

    private function seedPages(): void
    {
        Page::create([
            'title' => 'About Us',
            'content' => '<div class="prose max-w-none">
                <h2>About ShoppingCart</h2>
                <p class="lead">India\'s leading multi-vendor marketplace, connecting thousands of sellers with millions of customers since 2024.</p>
                <p>At ShoppingCart, we believe that shopping should be simple, convenient, and enjoyable. Our platform brings together verified vendors from across India, offering everything from the latest electronics and fashion to home essentials and more.</p>
                <h3>Our Mission</h3>
                <p>To make quality products accessible to everyone at fair prices, while empowering small and medium businesses to reach customers nationwide.</p>
                <h3>Why Choose Us?</h3>
                <ul>
                    <li><strong>Verified Sellers</strong> — Every vendor goes through a strict verification process</li>
                    <li><strong>Secure Payments</strong> — Multiple payment options with 100% buyer protection</li>
                    <li><strong>Easy Returns</strong> — Hassle-free 30-day return policy</li>
                    <li><strong>Fast Delivery</strong> — Pan-India delivery with real-time tracking</li>
                    <li><strong>24/7 Support</strong> — Dedicated customer support team</li>
                </ul>
                <h3>Our Numbers</h3>
                <p>Over <strong>10,000+ products</strong> from <strong>500+ verified vendors</strong> across <strong>50+ categories</strong>. Serving customers in <strong>all 28 states</strong> of India.</p>
            </div>',
            'status' => 'published',
            'seo_title' => 'About Us - ShoppingCart | India\'s Trusted Online Marketplace',
            'seo_description' => 'Learn about ShoppingCart, India\'s leading multi-vendor marketplace connecting sellers with customers since 2024.',
        ]);

        Page::create([
            'title' => 'Contact Us',
            'content' => '<div class="prose max-w-none">
                <h2>Get in Touch</h2>
                <p>We\'d love to hear from you! Whether you have a question about our products, need help with an order, or want to become a seller, our team is here to help.</p>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 not-prose my-8">
                    <div class="bg-indigo-50 p-6 rounded-xl text-center">
                        <h3 class="font-bold text-lg mb-2">Email Us</h3>
                        <p class="text-gray-600">support@shoppingcart.com</p>
                        <p class="text-sm text-gray-500 mt-1">Response within 24 hours</p>
                    </div>
                    <div class="bg-indigo-50 p-6 rounded-xl text-center">
                        <h3 class="font-bold text-lg mb-2">Call Us</h3>
                        <p class="text-gray-600">+91 1800-123-4567</p>
                        <p class="text-sm text-gray-500 mt-1">Mon-Sat, 9AM - 9PM IST</p>
                    </div>
                    <div class="bg-indigo-50 p-6 rounded-xl text-center">
                        <h3 class="font-bold text-lg mb-2">Visit Us</h3>
                        <p class="text-gray-600">123 Commerce Street</p>
                        <p class="text-sm text-gray-500 mt-1">Mumbai, Maharashtra 400001</p>
                    </div>
                </div>
                <h3>For Business Inquiries</h3>
                <p>Want to sell on ShoppingCart? Contact our vendor team at <strong>vendors@shoppingcart.com</strong> or register directly on our platform.</p>
            </div>',
            'status' => 'published',
            'seo_title' => 'Contact Us - ShoppingCart',
            'seo_description' => 'Get in touch with ShoppingCart. Email, call, or visit us for any questions or support.',
        ]);

        Page::create([
            'title' => 'Privacy Policy',
            'content' => '<div class="prose max-w-none">
                <h2>Privacy Policy</h2>
                <p><em>Last updated: March 2026</em></p>
                <p>At ShoppingCart ("we", "us", or "our"), your privacy is paramount. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use our marketplace platform.</p>
                <h3>1. Information We Collect</h3>
                <p>We collect information you provide directly: name, email address, phone number, shipping addresses, payment information, and order history. We also collect device information, IP addresses, and browsing data through cookies.</p>
                <h3>2. How We Use Your Information</h3>
                <p>Your information is used to: process orders, communicate order updates, improve our services, personalize your experience, prevent fraud, and comply with legal obligations.</p>
                <h3>3. Information Sharing</h3>
                <p>We share your information with: vendors (to fulfill orders), payment processors, delivery partners, and as required by law. We never sell your personal data.</p>
                <h3>4. Data Security</h3>
                <p>We implement industry-standard security measures including encryption, secure socket layer (SSL) technology, and regular security audits to protect your data.</p>
                <h3>5. Your Rights</h3>
                <p>You have the right to access, update, or delete your personal information at any time through your account settings or by contacting us.</p>
                <h3>6. Contact</h3>
                <p>For privacy-related queries, contact our Data Protection Officer at <strong>privacy@shoppingcart.com</strong>.</p>
            </div>',
            'status' => 'published',
            'seo_title' => 'Privacy Policy - ShoppingCart',
            'seo_description' => 'Read ShoppingCart\'s privacy policy to understand how we collect, use, and protect your personal information.',
        ]);

        Page::create([
            'title' => 'Terms of Service',
            'content' => '<div class="prose max-w-none">
                <h2>Terms of Service</h2>
                <p><em>Last updated: March 2026</em></p>
                <p>Welcome to ShoppingCart. By accessing or using our platform, you agree to be bound by these Terms of Service.</p>
                <h3>1. Account Registration</h3>
                <p>You must be at least 18 years old to create an account. You are responsible for maintaining the confidentiality of your account credentials.</p>
                <h3>2. Ordering & Payment</h3>
                <p>All prices are listed in Indian Rupees (INR) and include applicable taxes unless stated otherwise. We accept multiple payment methods including UPI, cards, net banking, and cash on delivery.</p>
                <h3>3. Shipping & Delivery</h3>
                <p>Delivery times vary based on location and product availability. Free shipping is available on orders above ₹500. Real-time tracking is provided for all shipments.</p>
                <h3>4. Returns & Refunds</h3>
                <p>Most products can be returned within 30 days of delivery. Refunds are processed within 7-10 business days after the return is received and inspected.</p>
                <h3>5. Vendor Responsibilities</h3>
                <p>Vendors are independent sellers responsible for the quality and accuracy of their product listings. ShoppingCart facilitates the marketplace but does not manufacture products.</p>
                <h3>6. Limitation of Liability</h3>
                <p>ShoppingCart shall not be liable for any indirect, incidental, or consequential damages arising from the use of our platform.</p>
            </div>',
            'status' => 'published',
            'seo_title' => 'Terms of Service - ShoppingCart',
            'seo_description' => 'Read ShoppingCart\'s terms of service covering accounts, orders, shipping, returns, and more.',
        ]);

        Page::create([
            'title' => 'Refund Policy',
            'content' => '<div class="prose max-w-none">
                <h2>Refund Policy</h2>
                <p>We want you to be completely satisfied with your purchase. If you\'re not, here\'s how our refund policy works.</p>
                <h3>Eligibility</h3>
                <p>Items must be unused, in original packaging, and returned within 30 days of delivery. Electronics must include all accessories and warranty cards.</p>
                <h3>Refund Process</h3>
                <p>Once we receive your return, we\'ll inspect the item and process your refund within 7-10 business days. Refunds are credited to the original payment method.</p>
                <h3>Non-Refundable Items</h3>
                <p>Personal care products, undergarments, customized items, and digital downloads are non-refundable unless defective.</p>
            </div>',
            'status' => 'published',
        ]);
    }

    private function seedBanners(): void
    {
        $bannerImages = [
            ['url' => 'https://picsum.photos/seed/banner-electronics/1920/600', 'file' => 'banner-electronics.jpg'],
            ['url' => 'https://picsum.photos/seed/banner-arrivals/1920/600', 'file' => 'banner-arrivals.jpg'],
            ['url' => 'https://picsum.photos/seed/banner-fashion/1920/600', 'file' => 'banner-fashion.jpg'],
        ];

        $banners = [
            [
                'title' => 'Summer Electronics Sale',
                'subtitle' => 'Up to 50% off on phones, laptops & accessories',
                'link' => '/shop?sort=price_asc',
                'position' => 'hero',
                'sort_order' => 1,
                'is_active' => true,
                'starts_at' => now()->subDays(5),
                'expires_at' => now()->addDays(25),
            ],
            [
                'title' => 'New Arrivals',
                'subtitle' => 'Discover the latest products from top brands',
                'link' => '/shop?sort=newest',
                'position' => 'hero',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'title' => 'Fashion Week Special',
                'subtitle' => 'Trending styles at unbeatable prices',
                'link' => '/category/fashion',
                'position' => 'hero',
                'sort_order' => 3,
                'is_active' => true,
            ],
        ];

        foreach ($banners as $i => $data) {
            $imagePath = '';
            try {
                $response = Http::timeout(10)->get($bannerImages[$i]['url']);
                if ($response->successful()) {
                    $path = 'banners/' . $bannerImages[$i]['file'];
                    Storage::disk('public')->put($path, $response->body());
                    $imagePath = $path;
                }
            } catch (\Exception $e) {
                // Skip — gradient fallback will show
            }

            Banner::create(array_merge($data, ['image' => $imagePath]));
        }

        Banner::create([
            'title' => 'Free Shipping',
            'subtitle' => 'On all orders above ₹500',
            'image' => '',
            'link' => '/shop',
            'position' => 'sidebar',
            'sort_order' => 1,
            'is_active' => true,
        ]);
    }

    private function seedBlog(): void
    {
        $admin = User::where('email', 'admin@shoppingcart.com')->first();

        $techCat = BlogCategory::create(['name' => 'Technology', 'status' => 'active']);
        $dealsCat = BlogCategory::create(['name' => 'Deals & Offers', 'status' => 'active']);
        $guidesCat = BlogCategory::create(['name' => 'Buying Guides', 'status' => 'active']);
        BlogCategory::create(['name' => 'News', 'status' => 'active']);

        BlogPost::create([
            'blog_category_id' => $techCat->id,
            'author_id' => $admin->id,
            'title' => 'Top 10 Smartphones of 2026: Our Expert Picks',
            'excerpt' => 'From flagship powerhouses to budget champions, we rank the best smartphones you can buy right now in India.',
            'content' => '<p>The smartphone market in 2026 is more competitive than ever. With AI-powered features becoming standard and camera technology reaching new heights, choosing the right phone can be overwhelming.</p>
                <h3>1. Samsung Galaxy S24 Ultra</h3>
                <p>The undisputed king of Android smartphones. With Galaxy AI, the S24 Ultra transforms how you interact with your phone — from real-time translation to circle-to-search.</p>
                <h3>2. Apple iPhone 15 Pro Max</h3>
                <p>Apple\'s best iPhone yet, featuring the A17 Pro chip and a stunning 5x telephoto camera. The titanium design makes it lighter than ever.</p>
                <h3>3. Samsung Galaxy A55 5G</h3>
                <p>The best mid-range phone of 2026. It proves you don\'t need to spend a fortune for flagship-level features.</p>
                <p>Stay tuned for our detailed reviews of each device!</p>',
            'status' => 'published',
            'published_at' => now()->subDays(3),
        ]);

        BlogPost::create([
            'blog_category_id' => $dealsCat->id,
            'author_id' => $admin->id,
            'title' => 'Best Deals This Week: Up to 50% Off Electronics',
            'excerpt' => 'Don\'t miss these incredible deals on smartphones, laptops, headphones, and more. Limited time offers you won\'t want to miss!',
            'content' => '<p>We\'ve scoured the platform to bring you the best deals this week. Here are our top picks:</p>
                <h3>Smartphones</h3>
                <ul><li>Samsung Galaxy S24 Ultra — ₹1,09,999 (Save ₹20,000)</li><li>Samsung Galaxy A55 5G — ₹32,999 (Save ₹7,000)</li></ul>
                <h3>Audio</h3>
                <ul><li>Sony WH-1000XM5 — ₹24,990 (Save ₹5,000)</li><li>Samsung Galaxy Buds3 Pro — ₹14,999 (Save ₹3,000)</li></ul>
                <h3>Fashion</h3>
                <ul><li>Nike Air Max 270 — ₹9,995 (Save ₹3,000)</li></ul>
                <p>All deals are subject to availability. Hurry before they\'re gone!</p>',
            'status' => 'published',
            'published_at' => now()->subDays(1),
        ]);

        BlogPost::create([
            'blog_category_id' => $guidesCat->id,
            'author_id' => $admin->id,
            'title' => 'Buying Guide: How to Choose the Perfect Laptop in 2026',
            'excerpt' => 'Whether you\'re a student, professional, or creative, this comprehensive guide will help you find the right laptop.',
            'content' => '<p>Choosing a laptop can be daunting with so many options available. Let\'s break it down by use case.</p>
                <h3>For Students</h3>
                <p>Look for lightweight designs with good battery life. The MacBook Air M3 is our top pick for students — it\'s thin, powerful, and lasts all day on a single charge.</p>
                <h3>For Professionals</h3>
                <p>If you need power for multitasking and business applications, the MacBook Pro 14" M3 Pro or Samsung Galaxy Book4 Pro are excellent choices.</p>
                <h3>Key Specs to Consider</h3>
                <ul><li><strong>RAM:</strong> Minimum 8GB, 16GB recommended</li><li><strong>Storage:</strong> 256GB SSD minimum, 512GB preferred</li><li><strong>Display:</strong> Full HD minimum, OLED for creative work</li><li><strong>Battery:</strong> 10+ hours for on-the-go use</li></ul>',
            'status' => 'published',
            'published_at' => now()->subDays(7),
        ]);

        BlogPost::create([
            'blog_category_id' => $techCat->id,
            'author_id' => $admin->id,
            'title' => 'The Rise of AI in Consumer Electronics',
            'excerpt' => 'How artificial intelligence is transforming everyday gadgets — from smartphones to home appliances.',
            'content' => '<p>AI is no longer just a buzzword — it\'s become an integral part of the devices we use daily.</p>
                <p>Samsung\'s Galaxy AI brings features like live translation, photo editing with generative fill, and smart search directly to your phone. Sony\'s headphones use AI to adapt noise cancellation in real-time based on your environment.</p>
                <p>Even home appliances are getting smarter. LG\'s AI-powered washing machines detect fabric types and adjust wash cycles automatically, saving water and energy.</p>
                <p>As AI continues to evolve, expect even more intelligent and personalized experiences from your everyday gadgets.</p>',
            'status' => 'published',
            'published_at' => now()->subDays(14),
        ]);
    }

    private function seedCoupons(): void
    {
        Coupon::create([
            'code' => 'WELCOME10',
            'type' => 'percentage',
            'value' => 10,
            'min_order_amount' => 500,
            'max_discount_amount' => 200,
            'max_uses' => 1000,
            'max_uses_per_user' => 1,
            'starts_at' => now()->subDays(30),
            'expires_at' => now()->addDays(60),
            'status' => 'active',
        ]);

        Coupon::create([
            'code' => 'FLAT500',
            'type' => 'fixed',
            'value' => 500,
            'min_order_amount' => 5000,
            'max_uses' => 500,
            'max_uses_per_user' => 2,
            'starts_at' => now()->subDays(10),
            'expires_at' => now()->addDays(20),
            'status' => 'active',
        ]);

        Coupon::create([
            'code' => 'FREESHIP',
            'type' => 'free_shipping',
            'value' => 0,
            'min_order_amount' => 299,
            'max_uses' => null,
            'starts_at' => now(),
            'expires_at' => now()->addDays(90),
            'status' => 'active',
        ]);

        Coupon::create([
            'code' => 'SUMMER25',
            'type' => 'percentage',
            'value' => 25,
            'min_order_amount' => 2000,
            'max_discount_amount' => 1000,
            'max_uses' => 200,
            'max_uses_per_user' => 1,
            'starts_at' => now(),
            'expires_at' => now()->addDays(30),
            'status' => 'active',
        ]);
    }

    private function seedAddresses(User $customer1, User $customer2): void
    {
        Address::create([
            'user_id' => $customer1->id,
            'type' => 'shipping',
            'name' => 'Rahul Sharma',
            'phone' => '+91 9876543210',
            'address_line_1' => '42, Sunshine Apartments',
            'address_line_2' => 'MG Road, Andheri West',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'postal_code' => '400058',
            'country' => 'India',
            'is_default' => true,
        ]);

        Address::create([
            'user_id' => $customer1->id,
            'type' => 'shipping',
            'name' => 'Rahul Sharma',
            'phone' => '+91 9876543210',
            'address_line_1' => '15, Tech Park Road',
            'address_line_2' => 'Whitefield',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'postal_code' => '560066',
            'country' => 'India',
            'is_default' => false,
        ]);

        Address::create([
            'user_id' => $customer2->id,
            'type' => 'shipping',
            'name' => 'Priya Patel',
            'phone' => '+91 9876543211',
            'address_line_1' => '88, Lakeview Colony',
            'address_line_2' => 'Jubilee Hills',
            'city' => 'Hyderabad',
            'state' => 'Telangana',
            'postal_code' => '500033',
            'country' => 'India',
            'is_default' => true,
        ]);
    }
}
