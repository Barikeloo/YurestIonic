<?php

namespace Tests\Unit\Menu\Domain\ValueObject;

use App\Menu\Domain\ValueObject\MenuSectionName;
use PHPUnit\Framework\TestCase;

class MenuSectionNameTest extends TestCase
{
    public function test_create_with_valid_name(): void
    {
        $name = MenuSectionName::create('Primer plato');

        $this->assertSame('Primer plato', $name->value());
    }

    public function test_create_trims_whitespace(): void
    {
        $name = MenuSectionName::create('  Primer plato  ');

        $this->assertSame('Primer plato', $name->value());
    }

    public function test_create_with_empty_string_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Menu section name cannot be empty.');

        MenuSectionName::create('');
    }

    public function test_create_with_whitespace_only_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Menu section name cannot be empty.');

        MenuSectionName::create('   ');
    }

    public function test_create_with_excessive_length_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Menu section name cannot exceed 255 characters.');

        MenuSectionName::create(str_repeat('a', 256));
    }

    public function test_create_with_max_length(): void
    {
        $name = MenuSectionName::create(str_repeat('a', 255));

        $this->assertSame(255, strlen($name->value()));
    }
}
