<?php

namespace Tests\Unit\Cash\Domain\Entity;

use App\Cash\Domain\Entity\ZReport;
use App\Cash\Domain\ValueObject\ZReportNumber;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Money;
use App\Shared\Domain\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;

class ZReportEntityTest extends TestCase
{
    public function test_generate_creates_z_report_with_hash(): void
    {
        $restaurantId = Uuid::generate();
        $cashSessionId = Uuid::generate();

        $zReport = ZReport::generate(
            restaurantId: $restaurantId,
            cashSessionId: $cashSessionId,
            reportNumber: ZReportNumber::create(1),
            totalSales: Money::create(100000),
            totalCash: Money::create(70000),
            totalCard: Money::create(30000),
            totalOther: Money::create(0),
            cashIn: Money::create(50000),
            cashOut: Money::create(10000),
            tips: Money::create(5000),
            discrepancy: Money::create(0),
            salesCount: 10,
            cancelledSalesCount: 1,
        );

        $this->assertInstanceOf(Uuid::class, $zReport->id());
        $this->assertSame($restaurantId->value(), $zReport->restaurantId()->value());
        $this->assertSame($cashSessionId->value(), $zReport->cashSessionId()->value());
        $this->assertSame(1, $zReport->reportNumber()->value());
        $this->assertSame(100000, $zReport->totalSales()->toCents());
        $this->assertSame(70000, $zReport->totalCash()->toCents());
        $this->assertSame(30000, $zReport->totalCard()->toCents());
        $this->assertSame(0, $zReport->totalOther()->toCents());
        $this->assertSame(50000, $zReport->cashIn()->toCents());
        $this->assertSame(10000, $zReport->cashOut()->toCents());
        $this->assertSame(5000, $zReport->tips()->toCents());
        $this->assertSame(0, $zReport->discrepancy()->toCents());
        $this->assertSame(10, $zReport->salesCount());
        $this->assertSame(1, $zReport->cancelledSalesCount());
        $this->assertInstanceOf(DomainDateTime::class, $zReport->generatedAt());
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $zReport->reportHash()->value());
    }

    public function test_verify_hash_returns_true_for_valid_report(): void
    {
        $zReport = ZReport::generate(
            restaurantId: Uuid::generate(),
            cashSessionId: Uuid::generate(),
            reportNumber: ZReportNumber::create(1),
            totalSales: Money::create(100000),
            totalCash: Money::create(70000),
            totalCard: Money::create(30000),
            totalOther: Money::create(0),
            cashIn: Money::create(50000),
            cashOut: Money::create(10000),
            tips: Money::create(5000),
            discrepancy: Money::create(0),
            salesCount: 10,
            cancelledSalesCount: 1,
        );

        $this->assertTrue($zReport->verifyHash());
    }

    public function test_verify_hash_returns_false_when_tampered(): void
    {
        $cashSessionId = Uuid::generate();
        $generatedAt = new \DateTimeImmutable('2026-01-01 12:00:00');

        $zReport = ZReport::fromPersistence(
            id: Uuid::generate()->value(),
            restaurantId: Uuid::generate()->value(),
            cashSessionId: $cashSessionId->value(),
            reportNumber: 1,
            reportHash: 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
            totalSalesCents: 100000,
            totalCashCents: 70000,
            totalCardCents: 30000,
            totalOtherCents: 0,
            cashInCents: 50000,
            cashOutCents: 10000,
            tipsCents: 5000,
            discrepancyCents: 0,
            salesCount: 10,
            cancelledSalesCount: 1,
            generatedAt: $generatedAt,
        );

        $this->assertFalse($zReport->verifyHash());
    }

    public function test_from_persistence_rebuilds_z_report(): void
    {
        $id = Uuid::generate()->value();
        $restaurantId = Uuid::generate()->value();
        $sessionId = Uuid::generate()->value();
        $hash = hash('sha256', 'test-data');
        $now = new \DateTimeImmutable;

        $zReport = ZReport::fromPersistence(
            id: $id,
            restaurantId: $restaurantId,
            cashSessionId: $sessionId,
            reportNumber: 1,
            reportHash: $hash,
            totalSalesCents: 100000,
            totalCashCents: 70000,
            totalCardCents: 30000,
            totalOtherCents: 0,
            cashInCents: 50000,
            cashOutCents: 10000,
            tipsCents: 5000,
            discrepancyCents: 0,
            salesCount: 10,
            cancelledSalesCount: 1,
            generatedAt: $now,
        );

        $this->assertSame($id, $zReport->id()->value());
        $this->assertSame($restaurantId, $zReport->restaurantId()->value());
        $this->assertSame($sessionId, $zReport->cashSessionId()->value());
        $this->assertSame(1, $zReport->reportNumber()->value());
        $this->assertSame($hash, $zReport->reportHash()->value());
        $this->assertSame(100000, $zReport->totalSales()->toCents());
        $this->assertSame(10, $zReport->salesCount());
        $this->assertEquals($now, $zReport->generatedAt()->value());
    }

    public function test_to_array_returns_all_fields(): void
    {
        $zReport = ZReport::generate(
            restaurantId: Uuid::generate(),
            cashSessionId: Uuid::generate(),
            reportNumber: ZReportNumber::create(1),
            totalSales: Money::create(100000),
            totalCash: Money::create(70000),
            totalCard: Money::create(30000),
            totalOther: Money::create(0),
            cashIn: Money::create(50000),
            cashOut: Money::create(10000),
            tips: Money::create(5000),
            discrepancy: Money::create(0),
            salesCount: 10,
            cancelledSalesCount: 1,
        );

        $array = $zReport->toArray();

        $this->assertSame($zReport->id()->value(), $array['id']);
        $this->assertSame($zReport->totalSales()->toCents(), $array['total_sales_cents']);
        $this->assertSame(10, $array['sales_count']);
        $this->assertArrayHasKey('generated_at', $array);
    }
}
