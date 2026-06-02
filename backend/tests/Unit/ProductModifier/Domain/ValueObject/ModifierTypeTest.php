<?php

namespace Tests\Unit\ProductModifier\Domain\ValueObject;

use App\ProductModifier\Domain\ValueObject\ModifierType;
use PHPUnit\Framework\TestCase;

class ModifierTypeTest extends TestCase
{
    public function test_create_extra(): void
    {
        $type = ModifierType::extra();

        $this->assertInstanceOf(ModifierType::class, $type);
        $this->assertSame('extra', $type->value());
        $this->assertTrue($type->isExtra());
        $this->assertFalse($type->isAccompaniment());
    }

    public function test_create_accompaniment(): void
    {
        $type = ModifierType::accompaniment();

        $this->assertInstanceOf(ModifierType::class, $type);
        $this->assertSame('accompaniment', $type->value());
        $this->assertTrue($type->isAccompaniment());
        $this->assertFalse($type->isExtra());
    }

    public function test_create_with_valid_value(): void
    {
        $type = ModifierType::create('extra');

        $this->assertSame('extra', $type->value());
    }

    public function test_create_with_invalid_value_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ModifierType::create('invalid');
    }

    public function test_create_with_empty_string_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ModifierType::create('');
    }
}
