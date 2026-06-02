<?php

namespace Tests\Unit\Zone\Domain\ValueObject;

use App\Zone\Domain\ValueObject\ZoneName;
use PHPUnit\Framework\TestCase;

class ZoneNameTest extends TestCase
{
    public function test_create_with_valid_name(): void
    {
        $name = ZoneName::create('Salon principal');

        $this->assertInstanceOf(ZoneName::class, $name);
        $this->assertSame('Salon principal', $name->value());
    }

    public function test_create_with_empty_string_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ZoneName::create('');
    }

    public function test_create_with_whitespace_only_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ZoneName::create('   ');
    }

    public function test_create_trims_whitespace(): void
    {
        $name = ZoneName::create('  Terraza  ');

        $this->assertSame('Terraza', $name->value());
    }

    public function test_create_with_excessive_length_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ZoneName::create(str_repeat('a', 256));
    }

    public function test_create_with_maximum_length(): void
    {
        $name = ZoneName::create(str_repeat('a', 255));

        $this->assertSame(255, strlen($name->value()));
    }
}
