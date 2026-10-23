<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PharmacyDemoSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Super Admin Users
        $superAdminRole = Role::where('slug', 'super_admin')->first();
        
        // Requested Super Admin
        $superAdminUser = User::updateOrCreate(
            ['email' => 'admin@pharmacy.com'],
            [
                'name' => 'System Super Admin',
                'password' => Hash::make('Password123!'),
                'phone' => '+91 9999999999',
                'status' => 'active',
            ]
        );
        $superAdminUser->roles()->syncWithoutDetaching([$superAdminRole->id]);

        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@pharmaerp.local'],
            [
                'name' => 'Apex ERP Super Administrator',
                'password' => Hash::make('Password123!'),
                'phone' => '+91 9876543210',
                'status' => 'active',
            ]
        );
        $superAdmin->roles()->syncWithoutDetaching([$superAdminRole->id]);

        // 2. Create Demo Organization
        $org = Organization::firstOrCreate(
            ['code' => 'APEX-PHARMA'],
            [
                'name' => 'Apex Healthcare & Pharmaceuticals',
                'drug_license_number' => 'DL-KA-2026-88741',
                'gstin' => '29AAACA1234A1Z5',
                'pan' => 'AAACA1234A',
                'email' => 'contact@apexpharma.com',
                'phone' => '+91 80 2345 6789',
                'address' => 'Plot 42, Brigade Towers, MG Road',
                'city' => 'Bengaluru',
                'state' => 'Karnataka',
                'postal_code' => '560001',
                'country' => 'India',
                'status' => 'active',
                'settings' => [
                    'currency' => 'INR',
                    'currency_symbol' => '₹',
                    'fefo_enforced' => true,
                    'allow_negative_stock' => false,
                    'tax_system' => 'GST',
                ]
            ]
        );

        // 3. Create Main Retail Branch
        $retailBranch = Branch::firstOrCreate(
            ['organization_id' => $org->id, 'code' => 'APEX-RET-01'],
            [
                'name' => 'Apex Medico - Indiranagar Retail',
                'business_type' => 'retail',
                'dl_number_20b' => 'KA-BNG-20B-99881',
                'dl_number_21b' => 'KA-BNG-21B-99882',
                'gstin' => '29AAACA1234A1Z5',
                'phone' => '+91 80 2525 1122',
                'email' => 'indiranagar@apexpharma.com',
                'address' => '100ft Road, Indiranagar',
                'city' => 'Bengaluru',
                'state' => 'Karnataka',
                'postal_code' => '560038',
                'invoice_prefix' => 'RET',
                'is_main_branch' => true,
                'status' => 'active',
            ]
        );

        // Create Retail Counter Warehouse
        Warehouse::firstOrCreate(
            ['branch_id' => $retailBranch->id, 'code' => 'RET-DISP-01'],
            [
                'organization_id' => $org->id,
                'name' => 'Front Counter Dispensary',
                'type' => 'retail_dispensary',
                'is_default' => true,
                'is_active' => true,
                'description' => 'Active dispensing counter rack for fast-moving medicines',
            ]
        );

        // 4. Create Wholesale Branch / Depot
        $wholesaleBranch = Branch::firstOrCreate(
            ['organization_id' => $org->id, 'code' => 'APEX-WHL-01'],
            [
                'name' => 'Apex Central Pharma Distribution Depot',
                'business_type' => 'wholesale',
                'dl_number_20b' => 'KA-BNG-20B-55441',
                'dl_number_21b' => 'KA-BNG-21B-55442',
                'gstin' => '29AAACA1234A1Z5',
                'phone' => '+91 80 2788 3344',
                'email' => 'depot@apexpharma.com',
                'address' => 'Peenya Industrial Area Phase 2',
                'city' => 'Bengaluru',
                'state' => 'Karnataka',
                'postal_code' => '560058',
                'invoice_prefix' => 'WHL',
                'is_main_branch' => false,
                'status' => 'active',
            ]
        );

        // Create Wholesale Warehouses
        Warehouse::firstOrCreate(
            ['branch_id' => $wholesaleBranch->id, 'code' => 'WHL-MAIN-GD'],
            [
                'organization_id' => $org->id,
                'name' => 'Peenya Central Godown',
                'type' => 'main_godown',
                'is_default' => true,
                'is_active' => true,
                'description' => 'Bulk pallet storage for cartons and wholesale cases',
            ]
        );

        Warehouse::firstOrCreate(
            ['branch_id' => $wholesaleBranch->id, 'code' => 'WHL-COLD-01'],
            [
                'organization_id' => $org->id,
                'name' => 'Vaccine & Insulin Cold Storage (2°C - 8°C)',
                'type' => 'cold_storage',
                'is_default' => false,
                'is_active' => true,
                'description' => 'Temperature-controlled chamber monitored 24/7',
            ]
        );

        // 5. Create Staff Accounts
        $ownerRole = Role::where('slug', 'organization_owner')->first();
        $pharmacistRole = Role::where('slug', 'pharmacist')->first();
        $cashierRole = Role::where('slug', 'cashier')->first();
        $purchaseRole = Role::where('slug', 'purchase_manager')->first();

        // Assign default org/branch to Super Admin users so they can access tenant views smoothly
        $superAdminUser->update([
            'organization_id' => $org->id,
            'branch_id' => $retailBranch->id,
        ]);
        $superAdmin->update([
            'organization_id' => $org->id,
            'branch_id' => $retailBranch->id,
        ]);

        // Requested Specific Accounts:
        // 1. Organization Owner
        $apolloOwner = User::updateOrCreate(
            ['email' => 'owner@apollocare.com'],
            [
                'organization_id' => $org->id,
                'branch_id' => $retailBranch->id,
                'name' => 'Dr. Apollo (Owner)',
                'password' => Hash::make('Password123!'),
                'phone' => '+91 9888888888',
                'status' => 'active',
            ]
        );
        $apolloOwner->roles()->sync([$ownerRole->id]);

        // 2. Pharmacist
        $apolloPharmacist = User::updateOrCreate(
            ['email' => 'pharmacist@apollocare.com'],
            [
                'organization_id' => $org->id,
                'branch_id' => $retailBranch->id,
                'name' => 'Apollo Registered Pharmacist',
                'pharmacist_reg_number' => 'APOLLO-PH-2024-88',
                'password' => Hash::make('Password123!'),
                'phone' => '+91 9777777777',
                'status' => 'active',
            ]
        );
        $apolloPharmacist->roles()->sync([$pharmacistRole->id]);

        // 3. Cashier
        $apolloCashier = User::updateOrCreate(
            ['email' => 'cashier@apollocare.com'],
            [
                'organization_id' => $org->id,
                'branch_id' => $retailBranch->id,
                'name' => 'Apollo POS Cashier',
                'password' => Hash::make('Password123!'),
                'phone' => '+91 9666666666',
                'status' => 'active',
            ]
        );
        $apolloCashier->roles()->sync([$cashierRole->id]);

        // 4. Purchase Manager
        $apolloPurchaser = User::updateOrCreate(
            ['email' => 'purchaser@apollocare.com'],
            [
                'organization_id' => $org->id,
                'branch_id' => $wholesaleBranch->id,
                'name' => 'Apollo Purchase Manager',
                'password' => Hash::make('Password123!'),
                'phone' => '+91 9555555555',
                'status' => 'active',
            ]
        );
        $apolloPurchaser->roles()->sync([$purchaseRole->id]);

        // Org Owner (Apex)
        $owner = User::firstOrCreate(
            ['email' => 'owner@apexpharma.com'],
            [
                'organization_id' => $org->id,
                'branch_id' => $retailBranch->id,
                'name' => 'Dr. Rajesh Sharma',
                'password' => Hash::make('Password123!'),
                'phone' => '+91 9900112233',
                'status' => 'active',
            ]
        );
        $owner->roles()->sync([$ownerRole->id]);

        // Registered Pharmacist
        $pharmacist = User::firstOrCreate(
            ['email' => 'pharmacist@apexpharma.com'],
            [
                'organization_id' => $org->id,
                'branch_id' => $retailBranch->id,
                'name' => 'Sumanth K. (R.Ph)',
                'pharmacist_reg_number' => 'KA-PHARM-2018-44910',
                'password' => Hash::make('Password123!'),
                'phone' => '+91 9845123456',
                'status' => 'active',
            ]
        );
        $pharmacist->roles()->sync([$pharmacistRole->id]);

        // Cashier
        $cashier = User::firstOrCreate(
            ['email' => 'cashier@apexpharma.com'],
            [
                'organization_id' => $org->id,
                'branch_id' => $retailBranch->id,
                'name' => 'Pooja Verma',
                'password' => Hash::make('Password123!'),
                'phone' => '+91 9741234567',
                'status' => 'active',
            ]
        );
        $cashier->roles()->sync([$cashierRole->id]);

        // Purchase Manager
        $purchaser = User::firstOrCreate(
            ['email' => 'purchase@apexpharma.com'],
            [
                'organization_id' => $org->id,
                'branch_id' => $wholesaleBranch->id,
                'name' => 'Arun Kumar',
                'password' => Hash::make('Password123!'),
                'phone' => '+91 9632123456',
                'status' => 'active',
            ]
        );
        $purchaser->roles()->sync([$purchaseRole->id]);
    }
}
