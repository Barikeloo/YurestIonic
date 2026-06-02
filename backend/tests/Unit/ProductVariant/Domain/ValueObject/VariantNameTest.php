<?php

namespace Tests\Unit\ProductVariant\Domain\ValueObject;

use App\ProductVariant\Domain\ValueObject\VariantName;
use PHPUnit\Framework\TestCase;

class VariantNameTest extends TestCase
{
    public function test_create_with_valid_name(): void
    {
        $name = VariantName::create('Rojo');

        $this->assertInstanceOf(VariantName::class, $name);
        $this->assertSame('Rojo', $name->value());
    }

    public function test_create_trims_whitespace(): void
    {
        $name = VariantName::create('  Rojo  ');

        $this->assertSame('Rojo', $name->value());
    }

    public function test_create_with_empty_string_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        VariantName::create('');
    }

    public function test_create_with_whitespace_only_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        VariantName::create('   ');
    }

    public function test_create_with_exceeding_max_length_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        VariantName::create(str_repeat('a', 256));
    }

    public function test_create_with_max_length(): void
    {
        $value = str_repeat('a', 255);
        $name = VariantName::create($value);

        $this->assertSame($value, $name->value());
    }
}
