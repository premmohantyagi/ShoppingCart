<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // General
            ['group' => 'general', 'key' => 'site_name', 'value' => 'ShoppingCart', 'type' => 'string'],
            ['group' => 'general', 'key' => 'site_tagline', 'value' => 'Your Marketplace, Your Way', 'type' => 'string'],
            ['group' => 'general', 'key' => 'site_logo', 'value' => null, 'type' => 'string'],
            ['group' => 'general', 'key' => 'site_favicon', 'value' => null, 'type' => 'string'],
            ['group' => 'general', 'key' => 'currency', 'value' => 'INR', 'type' => 'string'],
            ['group' => 'general', 'key' => 'currency_symbol', 'value' => '₹', 'type' => 'string'],
            ['group' => 'general', 'key' => 'contact_email', 'value' => 'support@shoppingcart.com', 'type' => 'string'],
            ['group' => 'general', 'key' => 'contact_phone', 'value' => '+91 9999999999', 'type' => 'string'],

            // Tax
            ['group' => 'tax', 'key' => 'tax_enabled', 'value' => '1', 'type' => 'boolean'],
            ['group' => 'tax', 'key' => 'default_tax_rate', 'value' => '18', 'type' => 'integer'],
            ['group' => 'tax', 'key' => 'prices_include_tax', 'value' => '0', 'type' => 'boolean'],

            // Shipping
            ['group' => 'shipping', 'key' => 'shipping_enabled', 'value' => '1', 'type' => 'boolean'],
            ['group' => 'shipping', 'key' => 'default_shipping_fee', 'value' => '50', 'type' => 'integer'],
            ['group' => 'shipping', 'key' => 'free_shipping_threshold', 'value' => '500', 'type' => 'integer'],

            // Inventory
            ['group' => 'inventory', 'key' => 'low_stock_threshold', 'value' => '10', 'type' => 'integer'],
            ['group' => 'inventory', 'key' => 'out_of_stock_hide', 'value' => '0', 'type' => 'boolean'],
            ['group' => 'inventory', 'key' => 'stock_reservation_minutes', 'value' => '30', 'type' => 'integer'],

            // Vendor
            ['group' => 'vendor', 'key' => 'vendor_registration_enabled', 'value' => '1', 'type' => 'boolean'],
            ['group' => 'vendor', 'key' => 'default_commission_type', 'value' => 'percentage', 'type' => 'string'],
            ['group' => 'vendor', 'key' => 'default_commission_value', 'value' => '10', 'type' => 'integer'],
            ['group' => 'vendor', 'key' => 'vendor_auto_approve', 'value' => '0', 'type' => 'boolean'],

            // SEO
            ['group' => 'seo', 'key' => 'meta_title', 'value' => 'ShoppingCart - Online Marketplace', 'type' => 'string'],
            ['group' => 'seo', 'key' => 'meta_description', 'value' => 'Shop the best products from trusted vendors on ShoppingCart marketplace.', 'type' => 'string'],
            ['group' => 'seo', 'key' => 'meta_keywords', 'value' => 'shopping, marketplace, online store, ecommerce', 'type' => 'string'],
        ];

        foreach ($settings as $setting) {
            Setting::create($setting);
        }
    }
}
