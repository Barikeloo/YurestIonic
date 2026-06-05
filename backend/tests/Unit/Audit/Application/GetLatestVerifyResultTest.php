<?php

declare(strict_types=1);

namespace Tests\Unit\Audit\Application;

use App\Audit\Application\GetLatestVerifyResult\GetLatestVerifyResult;
use App\Audit\Application\GetLatestVerifyResult\GetLatestVerifyResultCommand;
use App\Audit\Domain\Interfaces\VerifyChainResultRepositoryInterface;
use App\Audit\Domain\ValueObject\VerifyChainResult;
use App\Shared\Domain\ValueObject\Uuid;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class GetLatestVerifyResultTest extends TestCase
{
    private VerifyChainResultRepositoryInterface&MockInterface $repository;
    private GetLatestVerifyResult $useCase;

    protected function setUp(): void
    {
        $this->repository = Mockery::mock(VerifyChainResultRepositoryInterface::class);
        $this->useCase = new GetLatestVerifyResult($this->repository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_returns_null_when_no_result_exists(): void
    {
        $restaurantId = Uuid::generate()->value();

        $this->repository
            ->shouldReceive('latestByRestaurant')
            ->once()
            ->andReturn(null);

        $response = ($this->useCase)(new GetLatestVerifyResultCommand($restaurantId));
        $payload = $response->toArray();

        $this->assertNull($payload['latest']);
    }

    public function test_returns_latest_verify_result_for_restaurant(): void
    {
        $restaurantUuid = Uuid::generate();
        $verifiedAt = new \DateTimeImmutable('2026-06-05 10:00:00');

        $result = VerifyChainResult::create(
            restaurantId: $restaurantUuid,
            isValid: true,
            totalEvents: 10,
            verifiedCount: 10,
            brokenEvents: [],
            firstBrokenIndex: null,
            verifiedAt: $verifiedAt,
        );

        $this->repository
            ->shouldReceive('latestByRestaurant')
            ->once()
            ->andReturn($result);

        $response = ($this->useCase)(new GetLatestVerifyResultCommand($restaurantUuid->value()));
        $payload = $response->toArray();

        $this->assertNotNull($payload['latest']);
        $this->assertTrue($payload['latest']['is_valid']);
        $this->assertSame(10, $payload['latest']['total_events']);
        $this->assertSame(10, $payload['latest']['verified_count']);
        $this->assertSame([], $payload['latest']['broken_events']);
        $this->assertNull($payload['latest']['first_broken_index']);
        $this->assertSame('2026-06-05T10:00:00+00:00', $payload['latest']['verified_at']);
    }

    public function test_serialises_broken_events_in_response(): void
    {
        $restaurantUuid = Uuid::generate();
        $verifiedAt = new \DateTimeImmutable('2026-06-05 11:00:00');

        $broken = [
            ['uuid' => 'some-uuid', 'expected_hash' => 'abc', 'actual_hash' => 'def'],
        ];

        $result = VerifyChainResult::create(
            restaurantId: $restaurantUuid,
            isValid: false,
            totalEvents: 5,
            verifiedCount: 4,
            brokenEvents: $broken,
            firstBrokenIndex: 2,
            verifiedAt: $verifiedAt,
        );

        $this->repository
            ->shouldReceive('latestByRestaurant')
            ->once()
            ->andReturn($result);

        $payload = ($this->useCase)(new GetLatestVerifyResultCommand($restaurantUuid->value()))->toArray();

        $this->assertFalse($payload['latest']['is_valid']);
        $this->assertSame($broken, $payload['latest']['broken_events']);
        $this->assertSame(2, $payload['latest']['first_broken_index']);
    }
}
