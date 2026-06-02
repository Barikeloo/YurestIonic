<?php

namespace Tests\Unit\Menu\Domain\ValueObject;

use App\Menu\Domain\ValueObject\MenuDescription;
use PHPUnit\Framework\TestCase;

class MenuDescriptionTest extends TestCase
{
    public function test_create_with_valid_description(): void
    {
        $description = MenuDescription::create('Un menú muy completo');

        $this->assertSame('Un menú muy completo', $description->value());
        $this->assertFalse($description->isEmpty());
    }

    public function test_create_with_null_returns_empty(): void
    {
        $description = MenuDescription::create(null);

        $this->assertNull($description->value());
        $this->assertTrue($description->isEmpty());
    }

    public function test_create_with_empty_string_returns_empty(): void
    {
        $description = MenuDescription::create('');

        $this->assertNull($description->value());
        $this->assertTrue($description->isEmpty());
    }

    public function test_create_with_whitespace_only_returns_empty(): void
    {
        $description = MenuDescription::create('   ');

        $this->assertNull($description->value());
        $this->assertTrue($description->isEmpty());
    }

    public function test_create_trims_whitespace(): void
    {
        $description = MenuDescription::create('  Descripción  ');

        $this->assertSame('Descripción', $description->value());
    }

    public function test_empty_named_constructor(): void
    {
        $description = MenuDescription::empty();

        $this->assertNull($description->value());
        $this->assertTrue($description->isEmpty());
    }

    public function test_create_with_excessive_length_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Menu description cannot exceed 2000 characters.');

        MenuDescription::create(str_repeat('a', 2001));
    }

    public function test_create_with_max_length(): void
    {
        $description = MenuDescription::create(str_repeat('a', 2000));

        $this->assertSame(2000, strlen($description->value()));
    }
}
