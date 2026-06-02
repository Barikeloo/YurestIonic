<?php

namespace Tests\Unit\ProductModifier\Domain\ValueObject;

use App\ProductModifier\Domain\ValueObject\ModifierSelectionType;
use PHPUnit\Framework\TestCase;

class ModifierSelectionTypeTest extends TestCase
{
    public function test_create_single(): void
    {
        $type = ModifierSelectionType::single();

        $this->assertInstanceOf(ModifierSelectionType::class, $type);
        $this->assertSame('single', $type->value());
        $this->assertTrue($type->isSingle());
        $this->assertFalse($type->isMulti());
    }

    public function test_create_multi(): void
    {
        $type = ModifierSelectionType::multi();

        $this->assertInstanceOf(ModifierSelectionType::class, $type);
        $this->assertSame('multi', $type->value());
        $this->assertTrue($type->isMulti());
        $this->assertFalse($type->isSingle());
    }

    public function test_create_with_valid_value(): void
    {
        $type = ModifierSelectionType::create('single');

        $this->assertSame('single', $type->value());
    }

    public function test_create_with_invalid_value_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ModifierSelectionType::create('invalid');
    }

    public function test_create_with_empty_string_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ModifierSelectionType::create('');
    }
}
