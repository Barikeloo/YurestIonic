<?php

namespace Tests\Unit\Shared\Domain\ValueObject;

use App\Shared\Domain\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;

class UuidTest extends TestCase
{
    public function test_create_with_valid_uuid(): void
    {
        $uuid = Uuid::create('550e8400-e29b-41d4-a716-446655440000');

        $this->assertInstanceOf(Uuid::class, $uuid);
        $this->assertSame('550e8400-e29b-41d4-a716-446655440000', $uuid->value());
    }

    public function test_create_with_invalid_uuid_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Uuid::create('not-a-uuid');
    }

    public function test_create_with_empty_string_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Uuid::create('');
    }

    public function test_generate_creates_valid_uuid(): void
    {
        $uuid = Uuid::generate();

        $this->assertInstanceOf(Uuid::class, $uuid);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $uuid->value()
        );
    }

    public function test_generate_produces_unique_values(): void
    {
        $uuid1 = Uuid::generate()->value();
        $uuid2 = Uuid::generate()->value();

        $this->assertNotSame($uuid1, $uuid2);
    }

    public function test_value_is_lowercased(): void
    {
        $uuid = Uuid::create('550E8400-E29B-41D4-A716-446655440000');

        $this->assertSame('550e8400-e29b-41d4-a716-446655440000', $uuid->value());
    }
}
