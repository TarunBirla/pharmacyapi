<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Sales
            ['name' => 'View Sales', 'slug' => 'sales.view', 'group' => 'Sales'],
            ['name' => 'Create Sale / Invoice', 'slug' => 'sales.create', 'group' => 'Sales'],
            ['name' => 'Post / Finalize Sale', 'slug' => 'sales.post', 'group' => 'Sales'],
            ['name' => 'Cancel / Void Sale', 'slug' => 'sales.cancel', 'group' => 'Sales'],
            ['name' => 'Override Line Discount', 'slug' => 'sales.discount.override', 'group' => 'Sales'],
            ['name' => 'Create Sales Return', 'slug' => 'returns.create', 'group' => 'Sales'],
            ['name' => 'Approve Sales Return', 'slug' => 'returns.approve', 'group' => 'Sales'],

            // Purchase
            ['name' => 'View Purchases', 'slug' => 'purchase.view', 'group' => 'Purchase'],
            ['name' => 'Create Purchase Order', 'slug' => 'purchase.create', 'group' => 'Purchase'],
            ['name' => 'Post Purchase Receipt', 'slug' => 'purchase.post', 'group' => 'Purchase'],
            ['name' => 'Manage Purchase Returns', 'slug' => 'purchase.returns', 'group' => 'Purchase'],

            // Inventory
            ['name' => 'View Inventory & Stock', 'slug' => 'inventory.view', 'group' => 'Inventory'],
            ['name' => 'Adjust Stock / Write-offs', 'slug' => 'inventory.adjust', 'group' => 'Inventory'],
            ['name' => 'Transfer Stock Between Warehouses', 'slug' => 'inventory.transfer', 'group' => 'Inventory'],
            ['name' => 'Audit Stock Counts', 'slug' => 'inventory.count', 'group' => 'Inventory'],
            ['name' => 'View Batch Details & Expiries', 'slug' => 'batches.view', 'group' => 'Inventory'],

            // Products
            ['name' => 'View Products', 'slug' => 'products.view', 'group' => 'Products'],
            ['name' => 'Create Product', 'slug' => 'products.create', 'group' => 'Products'],
            ['name' => 'Update Product Details', 'slug' => 'products.update', 'group' => 'Products'],
            ['name' => 'Update Product Pricing', 'slug' => 'products.price.update', 'group' => 'Products'],

            // CRM / Suppliers
            ['name' => 'Manage Customers & Doctors', 'slug' => 'customers.manage', 'group' => 'Partners'],
            ['name' => 'Manage Suppliers', 'slug' => 'suppliers.manage', 'group' => 'Partners'],

            // Accounts & GST
            ['name' => 'View Chart of Accounts', 'slug' => 'accounts.view', 'group' => 'Accounts'],
            ['name' => 'Record Payments & Receipts', 'slug' => 'accounts.payment.record', 'group' => 'Accounts'],
            ['name' => 'View Profit & Margins', 'slug' => 'reports.profit.view', 'group' => 'Reports'],
            ['name' => 'View GST & Tax Reports', 'slug' => 'reports.gst.view', 'group' => 'Reports'],

            // Organization & System
            ['name' => 'Manage Organizations', 'slug' => 'organizations.manage', 'group' => 'System'],
            ['name' => 'Manage Branches', 'slug' => 'branches.manage', 'group' => 'System'],
            ['name' => 'Manage Warehouses', 'slug' => 'warehouses.manage', 'group' => 'System'],
            ['name' => 'Manage Users & Permissions', 'slug' => 'users.manage', 'group' => 'System'],
            ['name' => 'View Audit Logs', 'slug' => 'audit_logs.view', 'group' => 'System'],
        ];

        foreach ($permissions as $permData) {
            Permission::firstOrCreate(['slug' => $permData['slug']], $permData);
        }

        $allPermissionIds = Permission::pluck('id')->toArray();

        // System Roles
        $rolesConfig = [
            'super_admin' => [
                'name' => 'Super Admin',
                'description' => 'Unrestricted system-wide administrator',
                'is_system' => true,
                'permissions' => $allPermissionIds,
            ],
            'organization_owner' => [
                'name' => 'Organization Owner',
                'description' => 'Complete control over organization, branches, financials and inventory',
                'is_system' => true,
                'permissions' => $allPermissionIds,
            ],
            'branch_manager' => [
                'name' => 'Branch Manager',
                'description' => 'Manages branch operations, staff, orders, and branch stock',
                'is_system' => true,
                'permissions' => Permission::whereNotIn('slug', ['organizations.manage'])->pluck('id')->toArray(),
            ],
            'pharmacist' => [
                'name' => 'Registered Pharmacist',
                'description' => 'Dispenses medications, verifies prescriptions, and oversees batch validations',
                'is_system' => true,
                'permissions' => Permission::whereIn('slug', [
                    'sales.view', 'sales.create', 'sales.post', 'returns.create',
                    'inventory.view', 'batches.view', 'products.view', 'customers.manage',
                ])->pluck('id')->toArray(),
            ],
            'cashier' => [
                'name' => 'POS Cashier',
                'description' => 'Fast checkout billing, payments, and shift register',
                'is_system' => true,
                'permissions' => Permission::whereIn('slug', [
                    'sales.view', 'sales.create', 'sales.post', 'inventory.view', 'batches.view', 'products.view',
                ])->pluck('id')->toArray(),
            ],
            'purchase_manager' => [
                'name' => 'Purchase Manager',
                'description' => 'Procurement, vendor purchase orders, and goods receipts',
                'is_system' => true,
                'permissions' => Permission::whereIn('slug', [
                    'purchase.view', 'purchase.create', 'purchase.post', 'purchase.returns',
                    'suppliers.manage', 'inventory.view', 'batches.view', 'products.view',
                ])->pluck('id')->toArray(),
            ],
            'inventory_manager' => [
                'name' => 'Inventory Manager',
                'description' => 'Warehouse stock movements, transfers, adjustments, and expiry control',
                'is_system' => true,
                'permissions' => Permission::whereIn('slug', [
                    'inventory.view', 'inventory.adjust', 'inventory.transfer', 'inventory.count',
                    'batches.view', 'products.view', 'warehouses.manage',
                ])->pluck('id')->toArray(),
            ],
            'accounts_manager' => [
                'name' => 'Accounts Manager',
                'description' => 'Financial accounting, bank books, payments, and GST compliance',
                'is_system' => true,
                'permissions' => Permission::whereIn('slug', [
                    'accounts.view', 'accounts.payment.record', 'reports.profit.view',
                    'reports.gst.view', 'sales.view', 'purchase.view',
                ])->pluck('id')->toArray(),
            ],
            'auditor' => [
                'name' => 'Auditor',
                'description' => 'Read-only audit inspector for logs, inventory and financials',
                'is_system' => true,
                'permissions' => Permission::whereIn('slug', [
                    'audit_logs.view', 'reports.profit.view', 'reports.gst.view',
                    'sales.view', 'purchase.view', 'inventory.view',
                ])->pluck('id')->toArray(),
            ],
        ];

        foreach ($rolesConfig as $slug => $config) {
            $role = Role::firstOrCreate(
                ['slug' => $slug, 'organization_id' => null],
                [
                    'name' => $config['name'],
                    'description' => $config['description'],
                    'is_system' => $config['is_system'],
                ]
            );

            $role->permissions()->sync($config['permissions']);
        }
    }
}
