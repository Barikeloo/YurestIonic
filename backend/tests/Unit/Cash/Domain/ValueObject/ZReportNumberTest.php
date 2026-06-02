<?php

namespace Tests\Unit\Cash\Domain\ValueObject;

use App\Cash\Domain\ValueObject\ZReportNumber;
use PHPUnit\Framework\TestCase;

class ZReportNumberTest extends TestCase
{
    public function test_create_with_valid_number(): void
    {
        $number = ZReportNumber::create(1);

        $this->assertInstanceOf(ZReportNumber::class, $number);
        $this->assertSame(1, $number->value());
    }

    public function test_create_with_large_number(): void
    {
        $number = ZReportNumber::create(9999);

        $this->assertSame(9999, $number->value());
    }

    public function test_create_with_zero_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ZReportNumber::create(0);
    }

    public function test_create_with_negative_number_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ZReportNumber::create(-1);
    }
}
