<?php

namespace Tests\Unit\Family;

use App\Family\Domain\Entity\Family;
use App\Family\Domain\Event\FamilyCreated;
use App\Family\Domain\Event\FamilyDeleted;
use App\Family\Domain\Event\FamilyUpdated;
use App\Family\Domain\ValueObject\FamilyName;
use App\Shared\Domain\ValueObject\DomainDateTime;
use PHPUnit\Framework\TestCase;

class FamilyEntityTest extends TestCase
{
    public function test_ddd_create_builds_entity_and_allows_lifecycle_changes(): void
    {
        $family = Family::dddCreate(FamilyName::create('Bebidas'));

        $this->assertInstanceOf(Family::class, $family);
        $this->assertSame('Bebidas', $family->name()->value());
        $this->assertTrue($family->isActive());
        $this->assertInstanceOf(DomainDateTime::class, $family->createdAt());
        $this->assertInstanceOf(DomainDateTime::class, $family->updatedAt());

        $family->rename(FamilyName::create('Bebidas Frias'));
        $this->assertSame('Bebidas Frias', $family->name()->value());

        $family->deactivate();
        $this->assertFalse($family->isActive());

        $family->activate();
        $this->assertTrue($family->isActive());
    }

    public function test_ddd_create_records_a_family_created_event(): void
    {
        $family = Family::dddCreate(FamilyName::create('Bebidas'));

        $events = $family->pullDomainEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(FamilyCreated::class, $events[0]);
        $this->assertSame([], $family->pullDomainEvents());
    }

    public function test_rename_records_a_family_updated_event_with_before_after(): void
    {
        $family = Family::fromPersistence('00000000-0000-4000-8000-000000000000', 'Bebidas', true, new \DateTimeImmutable(), new \DateTimeImmutable());

        $family->rename(FamilyName::create('Bebidas Frias'));

        $events = $family->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(FamilyUpdated::class, $events[0]);
        $this->assertSame(['name' => 'Bebidas'], $events[0]->auditBefore());
        $this->assertSame(['name' => 'Bebidas Frias'], $events[0]->auditAfter());
    }

    public function test_delete_records_a_family_deleted_event(): void
    {
        $family = Family::fromPersistence('00000000-0000-4000-8000-000000000000', 'Bebidas', true, new \DateTimeImmutable(), new \DateTimeImmutable());

        $family->delete();

        $events = $family->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(FamilyDeleted::class, $events[0]);
    }

    public function test_activate_and_deactivate_record_no_events(): void
    {
        $family = Family::fromPersistence('00000000-0000-4000-8000-000000000000', 'Bebidas', true, new \DateTimeImmutable(), new \DateTimeImmutable());

        $family->deactivate();
        $family->activate();

        // Toggling active state is not audited, so it records no domain events.
        $this->assertSame([], $family->pullDomainEvents());
    }
}
