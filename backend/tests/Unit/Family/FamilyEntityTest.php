<?php

namespace Tests\Unit\Family;

use App\Family\Domain\Entity\Family;
use App\Family\Domain\ValueObject\FamilyName;
use App\Shared\Domain\ValueObject\DomainDateTime;
use PHPUnit\Framework\TestCase;

class FamilyEntityTest extends TestCase
{
    public function test_ddd_create_builds_entity_and_allows_lifecycle_changes(): void
    {
        $family = Family::dddCreate(FamilyName::create('Bebidas'));

        $this->assertInstanceOf(Family::class, $family);
        $this->assertSame('Bebidas', $family->name());
        $this->assertTrue($family->isActive());
        $this->assertInstanceOf(DomainDateTime::class, $family->createdAt());
        $this->assertInstanceOf(DomainDateTime::class, $family->updatedAt());

        $family->rename(FamilyName::create('Bebidas Frias'));
        $this->assertSame('Bebidas Frias', $family->name());

        $family->deactivate();
        $this->assertFalse($family->isActive());

        $family->activate();
        $this->assertTrue($family->isActive());
    }
}
