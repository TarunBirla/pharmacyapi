<?php

namespace Tests\Feature;

use Tests\TestCase;

class DatabaseSetupRouteTest extends TestCase
{
    public function test_setup_database_route_returns_200_and_view(): void
    {
        $response = $this->get('/setup-database');

        $response->assertStatus(200);
        $response->assertSee('Pharmacy ERP Live Deployment Setup');
        $response->assertSee('SUCCESS 200 OK');
        $response->assertSee('admin@pharmacy.com');
    }
}
