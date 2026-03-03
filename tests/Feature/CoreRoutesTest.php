<?php

namespace Tests\Feature;

use Tests\TestCase;

class CoreRoutesTest extends TestCase
{
    public function test_home_route_is_accessible(): void
    {
        $response = $this->get('/');

        $response->assertOk();
    }
}
