<?php

namespace Tests\Unit\Product\Domain\ValueObject;

use App\Product\Domain\ValueObject\ProductImageSrc;
use PHPUnit\Framework\TestCase;

class ProductImageSrcTest extends TestCase
{
    public function test_create_with_valid_path(): void
    {
        $imageSrc = ProductImageSrc::create('/images/coke.png');

        $this->assertInstanceOf(ProductImageSrc::class, $imageSrc);
        $this->assertSame('/images/coke.png', $imageSrc->value());
    }

    public function test_create_with_null(): void
    {
        $imageSrc = ProductImageSrc::create(null);

        $this->assertNull($imageSrc->value());
    }

    public function test_create_with_empty_string_converts_to_null(): void
    {
        $imageSrc = ProductImageSrc::create('');

        $this->assertNull($imageSrc->value());
    }

    public function test_create_with_whitespace_only_converts_to_null(): void
    {
        $imageSrc = ProductImageSrc::create('   ');

        $this->assertNull($imageSrc->value());
    }

    public function test_create_with_excessive_length_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ProductImageSrc::create('/' . str_repeat('a', 255));
    }
}
