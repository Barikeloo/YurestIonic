<?php

namespace Tests\Unit\Family;

use App\Family\Domain\Entity\Family;
use App\Family\Domain\Event\FamilyCreated;
use App\Family\Domain\Event\FamilyDeleted;
use App\Family\Domain\Event\FamilyUpdated;
use App\Family\Domain\ValueObject\FamilyColor;
use App\Family\Domain\ValueObject\FamilyIcon;
use App\Family\Domain\ValueObject\FamilyName;
use App\Shared\Domain\ValueObject\DomainDateTime;
use PHPUnit\Framework\TestCase;

class FamilyEntityTest extends TestCase
{
    private function existing(): Family
    {
        return Family::fromPersistence('00000000-0000-4000-8000-000000000000', 'Bebidas', null, null, true, new \DateTimeImmutable(), new \DateTimeImmutable());
    }

    public function test_ddd_create_builds_entity_with_appearance_and_allows_lifecycle_changes(): void
    {
        $family = Family::dddCreate(
            FamilyName::create('Bebidas'),
            FamilyColor::create('#1A9E5A'),
            FamilyIcon::create('coins'),
        );

        $this->assertSame('Bebidas', $family->name()->value());
        $this->assertSame('#1a9e5a', $family->color()?->value()); // normalised to lowercase
        $this->assertSame('coins', $family->icon()?->value());
        $this->assertTrue($family->isActive());
        $this->assertInstanceOf(DomainDateTime::class, $family->createdAt());

        $family->update(FamilyName::create('Bebidas Frias'), null, null);
        $this->assertSame('Bebidas Frias', $family->name()->value());
        $this->assertNull($family->color());
        $this->assertNull($family->icon());

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

    public function test_update_records_a_family_updated_event_with_appearance_before_after(): void
    {
        $family = Family::fromPersistence('00000000-0000-4000-8000-000000000000', 'Bebidas', '#111111', 'star', true, new \DateTimeImmutable(), new \DateTimeImmutable());

        $family->update(FamilyName::create('Bebidas Frias'), FamilyColor::create('#222222'), FamilyIcon::create('gem'));

        $events = $family->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(FamilyUpdated::class, $events[0]);
        $this->assertSame(['name' => 'Bebidas', 'color' => '#111111', 'icon' => 'star'], $events[0]->auditBefore());
        $this->assertSame(['name' => 'Bebidas Frias', 'color' => '#222222', 'icon' => 'gem'], $events[0]->auditAfter());
    }

    public function test_delete_records_a_family_deleted_event(): void
    {
        $family = $this->existing();

        $family->delete();

        $events = $family->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(FamilyDeleted::class, $events[0]);
    }

    public function test_activate_and_deactivate_record_no_events(): void
    {
        $family = $this->existing();

        $family->deactivate();
        $family->activate();

        $this->assertSame([], $family->pullDomainEvents());
    }
}
