<?php

namespace Tests\Unit\Shared;

use App\Shared\Domain\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;

class UuidValueObjectTest extends TestCase
{
    public function test_create_with_valid_uuid_normalizes_to_lowercase(): void
    {
        $uuid = Uuid::create('A987FBC9-4BED-4078-8F07-9141BA07C9F3');

        $this->assertSame('a987fbc9-4bed-4078-8f07-9141ba07c9f3', $uuid->value());
    }

    public function test_create_with_invalid_uuid_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid UUID: not-a-uuid');

        Uuid::create('not-a-uuid');
    }

    public function test_generate_creates_valid_and_unique_v4_uuid(): void
    {
        $first = Uuid::generate();
        $second = Uuid::generate();

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $first->value()
        );
        $this->assertNotSame($first->value(), $second->value());
    }
}
