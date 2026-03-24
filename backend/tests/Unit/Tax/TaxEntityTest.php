<?php

namespace Tests\Unit\Tax;

use App\Tax\Domain\Entity\Tax;
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

        $this->assertSame('IVA General', $tax->name());
        $this->assertSame(21, $tax->percentage());

        $tax->update(
            TaxName::create('IVA Revisado'),
            TaxPercentage::create(10),
        );

        $this->assertSame('IVA Revisado', $tax->name());
        $this->assertSame(10, $tax->percentage());
    }
}
