<?php

namespace Tests\Unit\Tax;

use App\Tax\Domain\Entity\Tax;
use App\Tax\Domain\Event\TaxCreated;
use App\Tax\Domain\Event\TaxDeleted;
use App\Tax\Domain\Event\TaxUpdated;
use App\Tax\Domain\ValueObject\TaxName;
use App\Tax\Domain\ValueObject\TaxPercentage;
use PHPUnit\Framework\TestCase;

class TaxEntityTest extends TestCase
{
    public function test_ddd_create_builds_entity_and_can_be_updated(): void
    {
        $tax = Tax::dddCreate(
            TaxName::create('IVA General'),
            TaxPercentage::create(21),
        );

        $this->assertSame('IVA General', $tax->name()->value());
        $this->assertSame(21, $tax->percentage()->value());

        $tax->update(
            TaxName::create('IVA Revisado'),
            TaxPercentage::create(10),
        );

        $this->assertSame('IVA Revisado', $tax->name()->value());
        $this->assertSame(10, $tax->percentage()->value());
    }

    public function test_ddd_create_records_a_tax_created_event(): void
    {
        $tax = Tax::dddCreate(TaxName::create('IVA General'), TaxPercentage::create(21));

        $events = $tax->pullDomainEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(TaxCreated::class, $events[0]);
        // Buffer is cleared after pulling.
        $this->assertSame([], $tax->pullDomainEvents());
    }

    public function test_update_records_event_only_when_something_changes(): void
    {
        $tax = Tax::fromPersistence('00000000-0000-4000-8000-000000000000', 'IVA General', 21, new \DateTimeImmutable(), new \DateTimeImmutable());

        // No-op update records nothing.
        $tax->update(null, null);
        $this->assertSame([], $tax->pullDomainEvents());

        // Same values record nothing.
        $tax->update(TaxName::create('IVA General'), TaxPercentage::create(21));
        $this->assertSame([], $tax->pullDomainEvents());

        // A real change records a TaxUpdated.
        $tax->update(TaxName::create('IVA Reducido'), null);
        $events = $tax->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(TaxUpdated::class, $events[0]);
        $this->assertSame(['name' => 'IVA General', 'percentage' => 21], $events[0]->auditBefore());
        $this->assertSame(['name' => 'IVA Reducido', 'percentage' => 21], $events[0]->auditAfter());
    }

    public function test_delete_records_a_tax_deleted_event(): void
    {
        $tax = Tax::fromPersistence('00000000-0000-4000-8000-000000000000', 'IVA General', 21, new \DateTimeImmutable(), new \DateTimeImmutable());

        $tax->delete();

        $events = $tax->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(TaxDeleted::class, $events[0]);
    }
}
