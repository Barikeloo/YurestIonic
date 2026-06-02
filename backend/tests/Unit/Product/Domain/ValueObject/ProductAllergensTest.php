<?php

namespace Tests\Unit\Product\Domain\ValueObject;

use App\Product\Domain\ValueObject\ProductAllergens;
use PHPUnit\Framework\TestCase;

class ProductAllergensTest extends TestCase
{
    public function test_create_with_valid_allergens(): void
    {
        $allergens = ProductAllergens::create(['gluten', 'dairy']);

        $this->assertInstanceOf(ProductAllergens::class, $allergens);
        $this->assertSame(['dairy', 'gluten'], $allergens->values());
    }

    public function test_create_with_empty_array(): void
    {
        $allergens = ProductAllergens::create([]);

        $this->assertTrue($allergens->isEmpty());
        $this->assertSame([], $allergens->values());
    }

    public function test_empty_factory(): void
    {
        $allergens = ProductAllergens::empty();

        $this->assertTrue($allergens->isEmpty());
        $this->assertSame([], $allergens->values());
    }

    public function test_create_removes_duplicates(): void
    {
        $allergens = ProductAllergens::create(['gluten', 'gluten', 'dairy']);

        $this->assertSame(['dairy', 'gluten'], $allergens->values());
    }

    public function test_create_with_invalid_allergen_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ProductAllergens::create(['invalid_allergen']);
    }
}
