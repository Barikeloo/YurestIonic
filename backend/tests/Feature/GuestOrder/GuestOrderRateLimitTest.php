<?php

declare(strict_types=1);

namespace Tests\Feature\GuestOrder;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class GuestOrderRateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('throttle:30,1');
    }

    public function test_public_table_endpoint_is_throttled_after_limit(): void
    {
        $tenant  = $this->createTenantSession();
        $fixture = $this->createGuestOrderFixture($tenant);

        for ($i = 0; $i < 30; $i++) {
            $this->getJson("/api/public/table/{$fixture['token']}")->assertStatus(200);
        }

        $this->getJson("/api/public/table/{$fixture['token']}")
            ->assertStatus(429);
    }
}
