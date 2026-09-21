<?php

namespace Tests\Feature;

use Tests\TestCase;

final class ApplicationBootTest extends TestCase
{
    public function test_laravel_application_boots_and_json_health_endpoint_is_available(): void
    {
        $this->getJson('/up')
            ->assertOk()
            ->assertExactJson(['status' => 'up']);
    }
}
