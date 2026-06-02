<?php

namespace Tests\Unit\Cash\Domain\ValueObject;

use App\Cash\Domain\ValueObject\CashSessionStatus;
use PHPUnit\Framework\TestCase;

class CashSessionStatusTest extends TestCase
{
    public function test_create_with_valid_status_open(): void
    {
        $status = CashSessionStatus::create('open');

        $this->assertTrue($status->isOpen());
        $this->assertFalse($status->isClosing());
        $this->assertFalse($status->isClosed());
        $this->assertFalse($status->isAbandoned());
    }

    public function test_create_with_valid_status_closing(): void
    {
        $status = CashSessionStatus::create('closing');

        $this->assertFalse($status->isOpen());
        $this->assertTrue($status->isClosing());
    }

    public function test_create_with_valid_status_closed(): void
    {
        $status = CashSessionStatus::create('closed');

        $this->assertTrue($status->isClosed());
    }

    public function test_create_with_valid_status_abandoned(): void
    {
        $status = CashSessionStatus::create('abandoned');

        $this->assertTrue($status->isAbandoned());
    }

    public function test_open_named_constructor(): void
    {
        $status = CashSessionStatus::open();

        $this->assertTrue($status->isOpen());
        $this->assertSame('open', $status->value());
    }

    public function test_closing_named_constructor(): void
    {
        $status = CashSessionStatus::closing();

        $this->assertTrue($status->isClosing());
        $this->assertSame('closing', $status->value());
    }

    public function test_closed_named_constructor(): void
    {
        $status = CashSessionStatus::closed();

        $this->assertTrue($status->isClosed());
        $this->assertSame('closed', $status->value());
    }

    public function test_abandoned_named_constructor(): void
    {
        $status = CashSessionStatus::abandoned();

        $this->assertTrue($status->isAbandoned());
        $this->assertSame('abandoned', $status->value());
    }

    public function test_create_with_invalid_status_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        CashSessionStatus::create('invalid');
    }

    public function test_equals(): void
    {
        $status1 = CashSessionStatus::open();
        $status2 = CashSessionStatus::open();
        $status3 = CashSessionStatus::closed();

        $this->assertTrue($status1->equals($status2));
        $this->assertFalse($status1->equals($status3));
    }
}
