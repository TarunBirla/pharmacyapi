<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use Tests\TestCase;

class ProductCatalogTest extends TestCase
{
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $user = User::withoutGlobalScopes()->where('email', 'pharmacist@apexpharma.com')->first();
        $this->token = $user->createToken('PharmacistTest')->plainTextToken;
    }

    public function test_pharmacist_can_search_products_by_barcode(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/v1/products?search=8901117001234');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $items = $response->json('data.data');
        $this->assertNotEmpty($items);
        $this->assertEquals('MED-DOLO-650', $items[0]['code']);
    }

    public function test_pharmacist_can_fetch_equivalent_generic_substitutes(): void
    {
        $dolo = Product::where('code', 'MED-DOLO-650')->firstOrFail();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/v1/products/{$dolo->id}/substitutes");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $substitutes = $response->json('substitutes');
        $substituteCodes = collect($substitutes)->pluck('code')->all();

        // Calpol 650 has the exact same generic composition (Paracetamol 650mg)
        $this->assertContains('MED-CALPOL-650', $substituteCodes);
        $this->assertNotContains('MED-AUG-625', $substituteCodes);
    }

    public function test_pharmacist_can_create_new_medicine_with_conversions(): void
    {
        $owner = User::withoutGlobalScopes()->where('email', 'owner@apexpharma.com')->first();
        $ownerToken = $owner->createToken('OwnerTest')->plainTextToken;

        $tabUnit = Unit::where('code', 'TAB')->firstOrFail();
        $stripUnit = Unit::where('code', 'STRIP')->firstOrFail();

        $prodCode = 'MED-PAN-' . uniqid();

        $response = $this->withHeader('Authorization', 'Bearer ' . $ownerToken)
            ->postJson('/api/v1/products', [
                'code' => $prodCode,
                'name' => 'Pan 40 Tablet',
                'generic_name' => 'Pantoprazole 40mg',
                'dosage_form' => 'tablet',
                'strength' => '40 mg',
                'hsn_code' => '30049099',
                'gst_rate' => 12.00,
                'primary_unit_id' => $tabUnit->id,
                'secondary_unit_id' => $stripUnit->id,
                'unit_conversion_factor' => 15.0000,
                'packaging_type' => 'Strip of 15 Tablets',
                'prescription_type' => 'schedule_h',
                'storage_temperature' => 'room_temperature',
                'reorder_level' => 30.0000,
                'reorder_quantity' => 100.0000,
                'barcodes' => ['890' . rand(1000000000, 9999999999)],
                'compositions' => [
                    [
                        'active_ingredient' => 'Pantoprazole Sodium',
                        'strength' => 40,
                        'unit' => 'mg',
                    ]
                ]
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'code' => $prodCode,
                    'name' => 'Pan 40 Tablet',
                    'unit_conversion_factor' => 15,
                ]
            ]);

        $this->assertDatabaseHas('products', [
            'code' => $prodCode,
        ]);
    }
}
