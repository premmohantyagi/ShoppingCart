<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Products
            'product.view', 'product.create', 'product.update', 'product.delete', 'product.publish',
            // Categories
            'category.view', 'category.create', 'category.update', 'category.delete',
            // Brands
            'brand.view', 'brand.create', 'brand.update', 'brand.delete',
            // Orders
            'order.view', 'order.update', 'order.cancel', 'order.delete',
            // Refunds
            'refund.view', 'refund.process',
            // Vendors
            'vendor.view', 'vendor.approve', 'vendor.suspend', 'vendor.delete',
            // Payouts
            'payout.view', 'payout.release',
            // Users / Customers
            'user.view', 'user.create', 'user.update', 'user.delete',
            // Reports
            'report.view', 'report.export',
            // CMS
            'cms.manage', 'blog.manage',
            // Banners
            'banner.manage',
            // Settings
            'settings.manage',
            // Inventory
            'inventory.view', 'inventory.update', 'warehouse.manage',
            // Coupons
            'coupon.view', 'coupon.create', 'coupon.update', 'coupon.delete',
            // Reviews
            'review.moderate',
            // Notifications
            'notification.manage',
            // Shipping
            'shipping.manage',
            // Tax
            'tax.manage',
            // Payment methods
            'payment_method.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions
        $superAdmin = Role::create(['name' => 'super_admin']);
        $superAdmin->givePermissionTo(Permission::all());

        $admin = Role::create(['name' => 'admin']);
        $admin->givePermissionTo(Permission::all());

        Role::create(['name' => 'product_manager'])->givePermissionTo([
            'product.view', 'product.create', 'product.update', 'product.delete', 'product.publish',
            'category.view', 'category.create', 'category.update', 'category.delete',
            'brand.view', 'brand.create', 'brand.update', 'brand.delete',
            'inventory.view', 'inventory.update',
            'review.moderate',
        ]);

        Role::create(['name' => 'order_manager'])->givePermissionTo([
            'order.view', 'order.update', 'order.cancel',
            'refund.view', 'refund.process',
            'shipping.manage',
        ]);

        Role::create(['name' => 'vendor_manager'])->givePermissionTo([
            'vendor.view', 'vendor.approve', 'vendor.suspend',
            'payout.view', 'payout.release',
        ]);

        Role::create(['name' => 'finance_manager'])->givePermissionTo([
            'order.view',
            'refund.view', 'refund.process',
            'payout.view', 'payout.release',
            'report.view', 'report.export',
            'tax.manage',
            'payment_method.manage',
        ]);

        Role::create(['name' => 'warehouse_manager'])->givePermissionTo([
            'inventory.view', 'inventory.update', 'warehouse.manage',
            'product.view',
            'order.view',
        ]);

        Role::create(['name' => 'support_staff'])->givePermissionTo([
            'order.view', 'order.update',
            'refund.view',
            'user.view',
            'review.moderate',
        ]);

        Role::create(['name' => 'content_manager'])->givePermissionTo([
            'cms.manage', 'blog.manage', 'banner.manage',
        ]);

        Role::create(['name' => 'vendor']);
        Role::create(['name' => 'customer']);
    }
}
