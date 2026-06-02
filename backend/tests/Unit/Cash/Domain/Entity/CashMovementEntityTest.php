<?php

namespace Tests\Unit\Cash\Domain\Entity;

use App\Cash\Domain\Entity\CashMovement;
use App\Cash\Domain\ValueObject\MovementReasonCode;
use App\Cash\Domain\ValueObject\MovementType;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Money;
use App\Shared\Domain\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;

class CashMovementEntityTest extends TestCase
{
    public function test_ddd_create_builds_movement(): void
    {
        $id = Uuid::generate();
        $restaurantId = Uuid::generate();
        $cashSessionId = Uuid::generate();
        $userId = Uuid::generate();

        $movement = CashMovement::dddCreate(
            id: $id,
            restaurantId: $restaurantId,
            cashSessionId: $cashSessionId,
            type: MovementType::in(),
            reasonCode: MovementReasonCode::changeRefill(),
            amount: Money::create(20000),
            userId: $userId,
        );

        $this->assertSame($id->value(), $movement->id()->value());
        $this->assertSame($restaurantId->value(), $movement->restaurantId()->value());
        $this->assertSame($id->value(), $movement->uuid()->value());
        $this->assertSame($cashSessionId->value(), $movement->cashSessionId()->value());
        $this->assertTrue($movement->type()->isIn());
        $this->assertTrue($movement->reasonCode()->equals(MovementReasonCode::changeRefill()));
        $this->assertSame(20000, $movement->amount()->toCents());
        $this->assertSame($userId->value(), $movement->userId()->value());
        $this->assertNull($movement->description());
        $this->assertInstanceOf(DomainDateTime::class, $movement->createdAt());
        $this->assertInstanceOf(DomainDateTime::class, $movement->updatedAt());
        $this->assertNull($movement->deletedAt());
    }

    public function test_ddd_create_with_description(): void
    {
        $movement = CashMovement::dddCreate(
            id: Uuid::generate(),
            restaurantId: Uuid::generate(),
            cashSessionId: Uuid::generate(),
            type: MovementType::out(),
            reasonCode: MovementReasonCode::supplierPayment(),
            amount: Money::create(100000),
            userId: Uuid::generate(),
            description: 'Pago a proveedor de carne',
        );

        $this->assertSame('Pago a proveedor de carne', $movement->description());
        $this->assertTrue($movement->type()->isOut());
    }

    public function test_from_persistence_rebuilds_movement(): void
    {
        $id = Uuid::generate()->value();
        $restaurantId = Uuid::generate()->value();
        $sessionId = Uuid::generate()->value();
        $userId = Uuid::generate()->value();
        $now = new \DateTimeImmutable;

        $movement = CashMovement::fromPersistence(
            id: $id,
            restaurantId: $restaurantId,
            uuid: $id,
            cashSessionId: $sessionId,
            type: 'out',
            reasonCode: 'sangria',
            amountCents: 5000,
            description: 'Retirada de efectivo',
            userId: $userId,
            createdAt: $now,
            updatedAt: $now,
        );

        $this->assertSame($id, $movement->id()->value());
        $this->assertSame($restaurantId, $movement->restaurantId()->value());
        $this->assertSame($sessionId, $movement->cashSessionId()->value());
        $this->assertTrue($movement->type()->isOut());
        $this->assertTrue($movement->reasonCode()->equals(MovementReasonCode::sangria()));
        $this->assertSame(5000, $movement->amount()->toCents());
        $this->assertSame('Retirada de efectivo', $movement->description());
        $this->assertSame($userId, $movement->userId()->value());
        $this->assertEquals($now, $movement->createdAt()->value());
        $this->assertEquals($now, $movement->updatedAt()->value());
        $this->assertNull($movement->deletedAt());
    }
}
