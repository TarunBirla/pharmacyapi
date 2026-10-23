<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Doctor;
use App\Models\Organization;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\SalesService;
use Illuminate\Database\Seeder;

class SalesAndPosSeeder extends Seeder
{
    public function run(): void
    {
        $org = Organization::where('code', 'APEX-PHARMA')->firstOrFail();
        $warehouse = Warehouse::where('code', 'RET-DISP-01')->firstOrFail();
        $cashier = User::where('email', 'cashier@apexpharma.com')->firstOrFail();

        // 1. Doctors
        $doctor = Doctor::firstOrCreate(
            ['registration_number' => 'KMC-2010-8812', 'organization_id' => $org->id],
            [
                'name' => 'Dr. Arvind Hegde (MD)',
                'specialization' => 'Internal Medicine & Cardiology',
                'phone' => '+91 80 2555 7788',
                'clinic_hospital_name' => 'Manipal Heart Foundation',
                'city' => 'Bengaluru',
            ]
        );

        // 2. Customers
        $patient = Customer::firstOrCreate(
            ['code' => 'CUST-RET-001', 'organization_id' => $org->id],
            [
                'name' => 'Ananya Deshmukh',
                'type' => 'retail',
                'phone' => '+91 9880011223',
                'email' => 'ananya.d@gmail.com',
                'credit_limit' => 5000.00,
                'credit_period_days' => 7,
                'status' => 'active',
                'city' => 'Bengaluru',
            ]
        );

        $dealer = Customer::firstOrCreate(
            ['code' => 'CUST-WHL-CITYLIFE', 'organization_id' => $org->id],
            [
                'name' => 'City Life Medical Stores & Pharmacy',
                'type' => 'wholesale_dealer',
                'phone' => '+91 80 2333 4455',
                'email' => 'purchase@citylifemed.in',
                'gstin' => '29AAACC9876C1Z4',
                'dl_number' => 'KA-BNG-20B-33441',
                'credit_limit' => 150000.00,
                'credit_period_days' => 21,
                'status' => 'active',
                'city' => 'Bengaluru',
            ]
        );

        // 3. Post Sample Retail POS sale with split payment via SalesService
        $doloBatch = ProductBatch::where('batch_number', 'DL-2026-F18M')->first();

        if ($doloBatch && (float)$doloBatch->quantity_available >= 5) {
            /** @var SalesService $salesService */
            $salesService = app(SalesService::class);

            $salesService->postSalesInvoice([
                'warehouse_id' => $warehouse->id,
                'customer_id' => $patient->id,
                'doctor_id' => $doctor->id,
                'patient_name' => 'Ananya Deshmukh',
                'doctor_name' => $doctor->name,
                'invoice_type' => 'retail',
                'items' => [
                    [
                        'product_id' => $doloBatch->product_id,
                        'product_batch_id' => $doloBatch->id,
                        'quantity' => 2,
                        'unit_price' => (float)$doloBatch->sale_price,
                        'discount_percent' => 0,
                    ]
                ],
                'payments' => [
                    [
                        'amount' => 30.00,
                        'payment_mode' => 'cash',
                    ],
                    [
                        'amount' => ((float)$doloBatch->sale_price * 2) - 30.00,
                        'payment_mode' => 'upi',
                        'transaction_reference' => 'UPI-AXIS-009911',
                    ]
                ]
            ], $cashier);
        }
    }
}
