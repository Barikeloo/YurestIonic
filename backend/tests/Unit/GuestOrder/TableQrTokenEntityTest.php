<?php

declare(strict_types=1);

namespace Tests\Unit\GuestOrder;

use App\GuestOrder\Domain\Entity\TableQrToken;
use App\GuestOrder\Domain\Event\TableQrTokenGenerated;
use App\Shared\Domain\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;

class TableQrTokenEntityTest extends TestCase
{
    public function test_ddd_create_generates_unique_token_and_id(): void
    {
        $tableId      = Uuid::generate();
        $restaurantId = Uuid::generate();

        $qrToken = TableQrToken::dddCreate($tableId, $restaurantId);

        $this->assertNotEmpty($qrToken->id()->value());
        $this->assertSame($tableId->value(), $qrToken->tableId()->value());
        $this->assertSame($restaurantId->value(), $qrToken->restaurantId()->value());
        $this->assertSame(1, $qrToken->catalogVersion());
        $this->assertSame(64, strlen($qrToken->token()->value()));
    }

    public function test_ddd_create_records_token_generated_event(): void
    {
        $qrToken = TableQrToken::dddCreate(Uuid::generate(), Uuid::generate());
        $events  = $qrToken->pullDomainEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(TableQrTokenGenerated::class, $events[0]);
    }

    public function test_regenerate_changes_token_value(): void
    {
        $qrToken      = TableQrToken::dddCreate(Uuid::generate(), Uuid::generate());
        $originalToken = $qrToken->token()->value();
        $qrToken->pullDomainEvents();

        $qrToken->regenerate();

        $this->assertNotSame($originalToken, $qrToken->token()->value());
    }

    public function test_regenerate_records_new_event(): void
    {
        $qrToken = TableQrToken::dddCreate(Uuid::generate(), Uuid::generate());
        $qrToken->pullDomainEvents();

        $qrToken->regenerate();
        $events = $qrToken->pullDomainEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(TableQrTokenGenerated::class, $events[0]);
    }

    public function test_increment_catalog_version(): void
    {
        $qrToken = TableQrToken::dddCreate(Uuid::generate(), Uuid::generate());
        $qrToken->pullDomainEvents();

        $qrToken->incrementCatalogVersion();
        $qrToken->incrementCatalogVersion();

        $this->assertSame(3, $qrToken->catalogVersion());
    }

    public function test_from_persistence_hydrates_correctly(): void
    {
        $id             = Uuid::generate()->value();
        $tableId        = Uuid::generate()->value();
        $restaurantId   = Uuid::generate()->value();
        $token          = str_repeat('a', 64);
        $now            = new \DateTimeImmutable();

        $qrToken = TableQrToken::fromPersistence($id, $tableId, $restaurantId, $token, 5, $now, $now);

        $this->assertSame($id, $qrToken->id()->value());
        $this->assertSame($tableId, $qrToken->tableId()->value());
        $this->assertSame($token, $qrToken->token()->value());
        $this->assertSame(5, $qrToken->catalogVersion());
    }
}
