<?php

namespace Tests\Feature\Api;

use Tests\TestCase;

class HealthTest extends TestCase
{
    public function test_api_health_endpoint_uses_the_response_contract(): void
    {
        $this->getJson('/api/v1/health')
            ->assertOk()
            ->assertExactJson([
                'success' => true,
                'data' => ['service' => 'qismat-api', 'version' => 'v1'],
                'message' => null,
            ]);
    }
}
