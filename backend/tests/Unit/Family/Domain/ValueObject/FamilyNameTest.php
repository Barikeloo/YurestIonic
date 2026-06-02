<?php

namespace Tests\Unit\Family\Domain\ValueObject;

use App\Family\Domain\ValueObject\FamilyName;
use PHPUnit\Framework\TestCase;

class FamilyNameTest extends TestCase
{
    public function test_create_with_valid_name(): void
    {
        $name = FamilyName::create('Entrantes');

        $this->assertInstanceOf(FamilyName::class, $name);
        $this->assertSame('Entrantes', $name->value());
    }

    public function test_create_with_empty_string_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        FamilyName::create('');
    }

    public function test_create_with_whitespace_only_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        FamilyName::create('   ');
    }

    public function test_create_trims_whitespace(): void
    {
        $name = FamilyName::create('  Postres  ');

        $this->assertSame('Postres', $name->value());
    }

    public function test_create_with_excessive_length_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        FamilyName::create(str_repeat('a', 256));
    }

    public function test_create_with_maximum_length(): void
    {
        $name = FamilyName::create(str_repeat('a', 255));

        $this->assertSame(255, strlen($name->value()));
    }
}
