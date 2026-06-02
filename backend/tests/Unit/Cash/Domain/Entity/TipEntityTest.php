<?php

namespace Tests\Unit\Cash\Domain\Entity;

use App\Cash\Domain\Entity\Tip;
use App\Cash\Domain\ValueObject\TipSource;
use App\Shared\Domain\ValueObject\Money;
use App\Shared\Domain\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;

class TipEntityTest extends TestCase
{
    public function test_ddd_create_builds_tip(): void
    {
        $id = Uuid::generate();
        $restaurantId = Uuid::generate();
        $saleId = Uuid::generate();
        $cashSessionId = Uuid::generate();

        $tip = Tip::dddCreate(
            id: $id,
            restaurantId: $restaurantId,
            saleId: $saleId,
            cashSessionId: $cashSessionId,
            amount: Money::create(500),
            source: 'card_added',
        );

        $this->assertSame($id->value(), $tip->id()->value());
        $this->assertSame($restaurantId->value(), $tip->restaurantId()->value());
        $this->assertSame($id->value(), $tip->uuid()->value());
        $this->assertSame($saleId->value(), $tip->saleId()->value());
        $this->assertSame($cashSessionId->value(), $tip->cashSessionId()->value());
        $this->assertSame(500, $tip->amount()->toCents());
        $this->assertTrue($tip->source()->isCardAdded());
        $this->assertNull($tip->beneficiaryUserId());
    }

    public function test_ddd_create_with_beneficiary(): void
    {
        $beneficiaryId = Uuid::generate();

        $tip = Tip::dddCreate(
            id: Uuid::generate(),
            restaurantId: Uuid::generate(),
            saleId: Uuid::generate(),
            cashSessionId: Uuid::generate(),
            amount: Money::create(1000),
            source: 'cash_declared',
            beneficiaryUserId: $beneficiaryId,
        );

        $this->assertTrue($tip->source()->isCashDeclared());
        $this->assertSame($beneficiaryId->value(), $tip->beneficiaryUserId()->value());
    }

    public function test_from_persistence_rebuilds_tip(): void
    {
        $id = Uuid::generate()->value();
        $restaurantId = Uuid::generate()->value();
        $saleId = Uuid::generate()->value();
        $sessionId = Uuid::generate()->value();
        $beneficiaryId = Uuid::generate()->value();
        $now = new \DateTimeImmutable;

        $tip = Tip::fromPersistence(
            id: $id,
            restaurantId: $restaurantId,
            uuid: $id,
            saleId: $saleId,
            cashSessionId: $sessionId,
            amountCents: 1500,
            source: 'card_added',
            beneficiaryUserId: $beneficiaryId,
            createdAt: $now,
            updatedAt: $now,
        );

        $this->assertSame($id, $tip->id()->value());
        $this->assertSame($restaurantId, $tip->restaurantId()->value());
        $this->assertSame($saleId, $tip->saleId()->value());
        $this->assertSame($sessionId, $tip->cashSessionId()->value());
        $this->assertSame(1500, $tip->amount()->toCents());
        $this->assertTrue($tip->source()->isCardAdded());
        $this->assertSame($beneficiaryId, $tip->beneficiaryUserId()->value());
        $this->assertEquals($now, $tip->createdAt()->value());
        $this->assertEquals($now, $tip->updatedAt()->value());
        $this->assertNull($tip->deletedAt());
    }
}
