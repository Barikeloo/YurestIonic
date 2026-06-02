<?php

namespace Tests\Unit\ProductModifier\Domain\ValueObject;

use App\ProductModifier\Domain\ValueObject\ModifierName;
use PHPUnit\Framework\TestCase;

class ModifierNameTest extends TestCase
{
    public function test_create_with_valid_name(): void
    {
        $name = ModifierName::create('Extra queso');

        $this->assertInstanceOf(ModifierName::class, $name);
        $this->assertSame('Extra queso', $name->value());
    }

    public function test_create_trims_whitespace(): void
    {
        $name = ModifierName::create('  Extra queso  ');

        $this->assertSame('Extra queso', $name->value());
    }

    public function test_create_with_empty_string_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ModifierName::create('');
    }

    public function test_create_with_whitespace_only_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ModifierName::create('   ');
    }

    public function test_create_with_exceeding_max_length_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ModifierName::create(str_repeat('a', 256));
    }

    public function test_create_with_max_length(): void
    {
        $value = str_repeat('a', 255);
        $name = ModifierName::create($value);

        $this->assertSame($value, $name->value());
    }
}
