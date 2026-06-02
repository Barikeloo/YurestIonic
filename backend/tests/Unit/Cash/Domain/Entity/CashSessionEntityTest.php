<?php

namespace Tests\Unit\Cash\Domain\Entity;

use App\Cash\Domain\Entity\CashSession;
use App\Cash\Domain\Exception\CashSessionAlreadyClosedException;
use App\Cash\Domain\Exception\CashSessionCannotCancelClosingException;
use App\Cash\Domain\Exception\CashSessionCannotCloseException;
use App\Cash\Domain\Exception\CashSessionCannotStartClosingException;
use App\Cash\Domain\ValueObject\CashSessionStatus;
use App\Cash\Domain\ValueObject\DeviceId;
use App\Cash\Domain\ValueObject\ZReportHash;
use App\Cash\Domain\ValueObject\ZReportNumber;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Money;
use App\Shared\Domain\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;

class CashSessionEntityTest extends TestCase
{
    private Uuid $id;
    private Uuid $restaurantId;
    private DeviceId $deviceId;
    private Uuid $openedByUserId;
    private Money $initialAmount;

    protected function setUp(): void
    {
        $this->id = Uuid::generate();
        $this->restaurantId = Uuid::generate();
        $this->deviceId = DeviceId::create('device-1');
        $this->openedByUserId = Uuid::generate();
        $this->initialAmount = Money::create(50000);
    }

    public function test_ddd_create_builds_open_session(): void
    {
        $session = CashSession::dddCreate(
            id: $this->id,
            restaurantId: $this->restaurantId,
            deviceId: $this->deviceId,
            openedByUserId: $this->openedByUserId,
            initialAmount: $this->initialAmount,
        );

        $this->assertSame($this->id->value(), $session->id()->value());
        $this->assertSame($this->restaurantId->value(), $session->restaurantId()->value());
        $this->assertSame($this->id->value(), $session->uuid()->value());
        $this->assertSame('device-1', $session->deviceId()->value());
        $this->assertSame($this->openedByUserId->value(), $session->openedByUserId()->value());
        $this->assertNull($session->closedByUserId());
        $this->assertSame(50000, $session->initialAmount()->toCents());
        $this->assertNull($session->finalAmount());
        $this->assertNull($session->expectedAmount());
        $this->assertNull($session->discrepancy());
        $this->assertNull($session->discrepancyReason());
        $this->assertNull($session->zReportNumber());
        $this->assertNull($session->zReportHash());
        $this->assertNull($session->notes());
        $this->assertTrue($session->status()->isOpen());
        $this->assertInstanceOf(DomainDateTime::class, $session->createdAt());
        $this->assertInstanceOf(DomainDateTime::class, $session->updatedAt());
        $this->assertNull($session->deletedAt());
    }

    public function test_ddd_create_with_notes(): void
    {
        $session = CashSession::dddCreate(
            id: $this->id,
            restaurantId: $this->restaurantId,
            deviceId: $this->deviceId,
            openedByUserId: $this->openedByUserId,
            initialAmount: $this->initialAmount,
            notes: 'Turno de mañana',
        );

        $this->assertSame('Turno de mañana', $session->notes());
    }

    public function test_start_closing_transitions_to_closing(): void
    {
        $session = $this->createOpenSession();
        $previousUpdatedAt = $session->updatedAt()->value();

        sleep(1);
        $session->startClosing();

        $this->assertTrue($session->status()->isClosing());
        $this->assertGreaterThan($previousUpdatedAt, $session->updatedAt()->value());
    }

    public function test_start_closing_when_not_open_throws_exception(): void
    {
        $session = $this->createOpenSession();
        $session->startClosing();

        $this->expectException(CashSessionCannotStartClosingException::class);

        $session->startClosing();
    }

    public function test_start_closing_when_closed_throws_exception(): void
    {
        $session = $this->createOpenSession();
        $session->startClosing();
        $session->close(
            closedByUserId: Uuid::generate(),
            finalAmount: Money::create(60000),
            expectedAmount: Money::create(60000),
            discrepancy: Money::create(0),
        );

        $this->expectException(CashSessionCannotStartClosingException::class);

        $session->startClosing();
    }

    public function test_cancel_closing_returns_to_open(): void
    {
        $session = $this->createOpenSession();
        $session->startClosing();
        $previousUpdatedAt = $session->updatedAt()->value();

        sleep(1);
        $session->cancelClosing();

        $this->assertTrue($session->status()->isOpen());
        $this->assertGreaterThan($previousUpdatedAt, $session->updatedAt()->value());
    }

    public function test_cancel_closing_when_not_closing_throws_exception(): void
    {
        $session = $this->createOpenSession();

        $this->expectException(CashSessionCannotCancelClosingException::class);

        $session->cancelClosing();
    }

    public function test_close_transitions_to_closed(): void
    {
        $session = $this->createOpenSession();
        $session->startClosing();

        $closedBy = Uuid::generate();
        $finalAmount = Money::create(60000);
        $expectedAmount = Money::create(59000);
        $discrepancy = Money::create(1000);
        $zReportNumber = ZReportNumber::create(1);
        $zReportHash = ZReportHash::create('abcdef1234567890abcdef1234567890abcdef1234567890abcdef1234567890');

        $session->close(
            closedByUserId: $closedBy,
            finalAmount: $finalAmount,
            expectedAmount: $expectedAmount,
            discrepancy: $discrepancy,
            zReportNumber: $zReportNumber,
            zReportHash: $zReportHash,
            discrepancyReason: 'Sobraba 10€ en caja',
        );

        $this->assertTrue($session->status()->isClosed());
        $this->assertSame($closedBy->value(), $session->closedByUserId()->value());
        $this->assertSame(60000, $session->finalAmount()->toCents());
        $this->assertSame(59000, $session->expectedAmount()->toCents());
        $this->assertSame(1000, $session->discrepancy()->toCents());
        $this->assertSame('Sobraba 10€ en caja', $session->discrepancyReason());
        $this->assertSame(1, $session->zReportNumber()->value());
        $this->assertInstanceOf(DomainDateTime::class, $session->closedAt());
    }

    public function test_close_without_z_report(): void
    {
        $session = $this->createOpenSession();
        $session->startClosing();

        $session->close(
            closedByUserId: Uuid::generate(),
            finalAmount: Money::create(50000),
            expectedAmount: Money::create(50000),
            discrepancy: Money::create(0),
        );

        $this->assertNull($session->zReportNumber());
        $this->assertNull($session->zReportHash());
        $this->assertNull($session->discrepancyReason());
    }

    public function test_close_when_not_closing_throws_exception(): void
    {
        $session = $this->createOpenSession();

        $this->expectException(CashSessionCannotCloseException::class);

        $session->close(
            closedByUserId: Uuid::generate(),
            finalAmount: Money::create(50000),
            expectedAmount: Money::create(50000),
            discrepancy: Money::create(0),
        );
    }

    public function test_force_close_sets_abandoned(): void
    {
        $session = $this->createOpenSession();
        $previousUpdatedAt = $session->updatedAt()->value();

        sleep(1);
        $session->forceClose(Uuid::generate());

        $this->assertTrue($session->status()->isAbandoned());
        $this->assertInstanceOf(DomainDateTime::class, $session->closedAt());
        $this->assertGreaterThan($previousUpdatedAt, $session->updatedAt()->value());
    }

    public function test_force_close_from_closing_sets_abandoned(): void
    {
        $session = $this->createOpenSession();
        $session->startClosing();

        $session->forceClose(Uuid::generate());

        $this->assertTrue($session->status()->isAbandoned());
    }

    public function test_force_close_when_already_closed_throws_exception(): void
    {
        $session = $this->createOpenSession();
        $session->startClosing();
        $session->close(
            closedByUserId: Uuid::generate(),
            finalAmount: Money::create(50000),
            expectedAmount: Money::create(50000),
            discrepancy: Money::create(0),
        );

        $this->expectException(CashSessionAlreadyClosedException::class);

        $session->forceClose(Uuid::generate());
    }

    public function test_force_close_when_already_abandoned_throws_exception(): void
    {
        $session = $this->createOpenSession();
        $session->forceClose(Uuid::generate());

        $this->expectException(CashSessionAlreadyClosedException::class);

        $session->forceClose(Uuid::generate());
    }

    public function test_from_persistence_rebuilds_full_session(): void
    {
        $id = Uuid::generate()->value();
        $uuid = Uuid::generate()->value();
        $restaurantId = Uuid::generate()->value();
        $openedBy = Uuid::generate()->value();
        $closedBy = Uuid::generate()->value();
        $now = new \DateTimeImmutable;

        $session = CashSession::fromPersistence(
            id: $id,
            restaurantId: $restaurantId,
            uuid: $uuid,
            deviceId: 'device-abc',
            openedByUserId: $openedBy,
            closedByUserId: $closedBy,
            openedAt: $now,
            closedAt: $now,
            initialAmountCents: 50000,
            finalAmountCents: 60000,
            expectedAmountCents: 59000,
            discrepancyCents: 1000,
            discrepancyReason: 'Diferencia',
            zReportNumber: 1,
            zReportHash: 'abcdef1234567890abcdef1234567890abcdef1234567890abcdef1234567890',
            notes: 'Turno noche',
            status: 'closed',
            createdAt: $now,
            updatedAt: $now,
        );

        $this->assertSame($id, $session->id()->value());
        $this->assertSame($restaurantId, $session->restaurantId()->value());
        $this->assertSame($uuid, $session->uuid()->value());
        $this->assertSame('device-abc', $session->deviceId()->value());
        $this->assertSame($openedBy, $session->openedByUserId()->value());
        $this->assertSame($closedBy, $session->closedByUserId()->value());
        $this->assertSame(50000, $session->initialAmount()->toCents());
        $this->assertSame(60000, $session->finalAmount()->toCents());
        $this->assertSame(59000, $session->expectedAmount()->toCents());
        $this->assertSame(1000, $session->discrepancy()->toCents());
        $this->assertSame('Diferencia', $session->discrepancyReason());
        $this->assertSame(1, $session->zReportNumber()->value());
        $this->assertSame(1, $session->zReportNumber()->value());
        $this->assertSame('Turno noche', $session->notes());
        $this->assertTrue($session->status()->isClosed());
        $this->assertEquals($now, $session->createdAt()->value());
        $this->assertEquals($now, $session->updatedAt()->value());
        $this->assertNull($session->deletedAt());
    }

    private function createOpenSession(): CashSession
    {
        return CashSession::dddCreate(
            id: $this->id,
            restaurantId: $this->restaurantId,
            deviceId: $this->deviceId,
            openedByUserId: $this->openedByUserId,
            initialAmount: $this->initialAmount,
        );
    }
}
