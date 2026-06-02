<?php

namespace Tests\Unit\Menu\Domain\ValueObject;

use App\Menu\Domain\ValueObject\MenuName;
use PHPUnit\Framework\TestCase;

class MenuNameTest extends TestCase
{
    public function test_create_with_valid_name(): void
    {
        $name = MenuName::create('Menú del día');

        $this->assertSame('Menú del día', $name->value());
    }

    public function test_create_trims_whitespace(): void
    {
        $name = MenuName::create('  Menú del día  ');

        $this->assertSame('Menú del día', $name->value());
    }

    public function test_create_with_empty_string_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Menu name cannot be empty.');

        MenuName::create('');
    }

    public function test_create_with_whitespace_only_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Menu name cannot be empty.');

        MenuName::create('   ');
    }

    public function test_create_with_excessive_length_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Menu name cannot exceed 255 characters.');

        MenuName::create(str_repeat('a', 256));
    }

    public function test_create_with_max_length(): void
    {
        $name = MenuName::create(str_repeat('a', 255));

        $this->assertSame(255, strlen($name->value()));
    }
}
