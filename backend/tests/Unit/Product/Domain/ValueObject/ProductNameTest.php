<?php

namespace Tests\Unit\Product\Domain\ValueObject;

use App\Product\Domain\ValueObject\ProductName;
use PHPUnit\Framework\TestCase;

class ProductNameTest extends TestCase
{
    public function test_create_with_valid_name(): void
    {
        $name = ProductName::create('Coca Cola');

        $this->assertInstanceOf(ProductName::class, $name);
        $this->assertSame('Coca Cola', $name->value());
    }

    public function test_create_with_empty_string_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ProductName::create('');
    }

    public function test_create_with_whitespace_only_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ProductName::create('   ');
    }

    public function test_create_trims_whitespace(): void
    {
        $name = ProductName::create('  Hamburguesa  ');

        $this->assertSame('Hamburguesa', $name->value());
    }

    public function test_create_with_excessive_length_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ProductName::create(str_repeat('a', 256));
    }

    public function test_create_with_maximum_length(): void
    {
        $name = ProductName::create(str_repeat('a', 255));

        $this->assertSame(255, strlen($name->value()));
    }
}
