<?php

namespace Tests\Unit\Tax\Domain\ValueObject;

use App\Tax\Domain\ValueObject\TaxName;
use PHPUnit\Framework\TestCase;

class TaxNameTest extends TestCase
{
    public function test_create_with_valid_name(): void
    {
        $name = TaxName::create('IVA General');

        $this->assertInstanceOf(TaxName::class, $name);
        $this->assertSame('IVA General', $name->value());
    }

    public function test_create_with_empty_string_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        TaxName::create('');
    }

    public function test_create_with_whitespace_only_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        TaxName::create('   ');
    }

    public function test_create_trims_whitespace(): void
    {
        $name = TaxName::create('  IVA Reducido  ');

        $this->assertSame('IVA Reducido', $name->value());
    }

    public function test_create_with_excessive_length_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        TaxName::create(str_repeat('a', 256));
    }

    public function test_create_with_maximum_length(): void
    {
        $name = TaxName::create(str_repeat('a', 255));

        $this->assertSame(255, strlen($name->value()));
    }
}
