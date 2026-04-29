<?php

namespace Tests\Unit\Shared;

use App\Shared\Domain\ValueObject\DomainDateTime;
use PHPUnit\Framework\TestCase;

class DomainDateTimeValueObjectTest extends TestCase
{
    public function test_create_with_given_datetime_keeps_value(): void
    {
        $dateTime = new \DateTimeImmutable('2026-01-15 10:30:00');
        $valueObject = DomainDateTime::create($dateTime);

        $this->assertSame('2026-01-15 10:30:00', $valueObject->format('Y-m-d H:i:s'));
        $this->assertEquals($dateTime, $valueObject->value());
    }

    public function test_now_returns_current_datetime(): void
    {
        $before = new \DateTimeImmutable;
        $valueObject = DomainDateTime::now();
        $after = new \DateTimeImmutable;

        $this->assertGreaterThanOrEqual($before->getTimestamp(), $valueObject->value()->getTimestamp());
        $this->assertLessThanOrEqual($after->getTimestamp(), $valueObject->value()->getTimestamp());
    }

    public function test_format_returns_formatted_datetime_string(): void
    {
        $valueObject = DomainDateTime::create(new \DateTimeImmutable('2026-04-10 22:11:33'));

        $this->assertSame('10/04/2026', $valueObject->format('d/m/Y'));
    }
}
