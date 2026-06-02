<?php

namespace Tests\Unit\Shared\Domain\ValueObject;

use App\Shared\Domain\ValueObject\DomainDateTime;
use PHPUnit\Framework\TestCase;

class DomainDateTimeTest extends TestCase
{
    public function test_create_with_specific_datetime(): void
    {
        $datetime = new \DateTimeImmutable('2025-06-01 12:30:00');
        $dto = DomainDateTime::create($datetime);

        $this->assertInstanceOf(DomainDateTime::class, $dto);
        $this->assertSame($datetime, $dto->value());
    }

    public function test_create_with_null_defaults_to_now(): void
    {
        $before = new \DateTimeImmutable;
        $dto = DomainDateTime::create();
        $after = new \DateTimeImmutable;

        $this->assertInstanceOf(DomainDateTime::class, $dto);
        $this->assertInstanceOf(\DateTimeImmutable::class, $dto->value());
        $this->assertGreaterThanOrEqual($before, $dto->value());
        $this->assertLessThanOrEqual($after, $dto->value());
    }

    public function test_now_creates_current_datetime(): void
    {
        $before = new \DateTimeImmutable;
        $dto = DomainDateTime::now();
        $after = new \DateTimeImmutable;

        $this->assertInstanceOf(DomainDateTime::class, $dto);
        $this->assertGreaterThanOrEqual($before, $dto->value());
        $this->assertLessThanOrEqual($after, $dto->value());
    }

    public function test_format_returns_correct_format(): void
    {
        $datetime = new \DateTimeImmutable('2025-06-01 15:30:45');
        $dto = DomainDateTime::create($datetime);

        $this->assertSame('2025-06-01', $dto->format('Y-m-d'));
        $this->assertSame('15:30:45', $dto->format('H:i:s'));
        $this->assertSame('2025-06-01 15:30:45', $dto->format('Y-m-d H:i:s'));
    }

    public function test_immutability_on_value_access(): void
    {
        $dto = DomainDateTime::now();
        $value = $dto->value();

        $this->assertInstanceOf(\DateTimeImmutable::class, $value);
    }

    public function test_microseconds_are_preserved(): void
    {
        $datetime = new \DateTimeImmutable('2025-06-01 12:00:00.123456');
        $dto = DomainDateTime::create($datetime);

        $this->assertSame('2025-06-01 12:00:00.123456', $dto->format('Y-m-d H:i:s.u'));
    }
}
