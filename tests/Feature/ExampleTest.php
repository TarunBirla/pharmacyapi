<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        // Root returns 200 and renders the public ERP landing page
        $response->assertStatus(200);
        $response->assertSee('APEX PHARMA');
        $response->assertSee('Launch POS Counter');
    }
}
