<?php

namespace Tests\Unit;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_home_route_returns_ok()
{
    $response = $this->get('/api/builds');
    $response->assertStatus(200);
}

}
