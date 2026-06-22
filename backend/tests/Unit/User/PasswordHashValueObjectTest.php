<?php

namespace Tests\Unit\User;

use App\User\Domain\ValueObject\PasswordHash;
use PHPUnit\Framework\TestCase;

class PasswordHashValueObjectTest extends TestCase
{
    public function test_create_with_minimum_length_hash(): void
    {
        $value = str_repeat('a', 60);
        $hash = PasswordHash::create($value);

        $this->assertSame($value, $hash->value());
    }

    public function test_create_with_maximum_length_hash(): void
    {
        $value = str_repeat('a', 255);
        $hash = PasswordHash::create($value);

        $this->assertSame($value, $hash->value());
    }

    public function test_create_with_short_hash_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        PasswordHash::create(str_repeat('a', 59));
    }

    public function test_create_with_too_long_hash_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        PasswordHash::create(str_repeat('a', 256));
    }
}
