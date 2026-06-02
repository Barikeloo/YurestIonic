<?php

namespace Tests\Unit\Audit\Domain\ValueObject;

use App\Audit\Domain\ValueObject\Category;
use PHPUnit\Framework\TestCase;

class CategoryTest extends TestCase
{
    /** @dataProvider validCategoryProvider */
    public function test_create_with_valid_category(string $value): void
    {
        $category = Category::create($value);

        $this->assertSame($value, $category->value());
    }

    public static function validCategoryProvider(): array
    {
        return [
            'order' => ['order'],
            'caja' => ['caja'],
            'sale' => ['sale'],
            'table' => ['table'],
            'catalog' => ['catalog'],
            'auth' => ['auth'],
            'config' => ['config'],
            'system' => ['system'],
        ];
    }

    public function test_create_with_invalid_category_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid audit category');

        Category::create('invalid');
    }

    public function test_create_with_empty_string_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Category::create('');
    }

    public function test_equals(): void
    {
        $c1 = Category::create('caja');
        $c2 = Category::create('caja');
        $c3 = Category::create('auth');

        $this->assertTrue($c1->equals($c2));
        $this->assertFalse($c1->equals($c3));
    }
}
