<?php

namespace Tests\Unit\Order;

use App\Order\Domain\ValueObject\OrderStatus;
use PHPUnit\Framework\TestCase;

class OrderStatusValueObjectTest extends TestCase
{
    public function test_create_open_status(): void
    {
        $status = OrderStatus::open();

        $this->assertInstanceOf(OrderStatus::class, $status);
        $this->assertSame('open', $status->value());
    }

    public function test_create_cancelled_status(): void
    {
        $status = OrderStatus::cancelled();

        $this->assertInstanceOf(OrderStatus::class, $status);
        $this->assertSame('cancelled', $status->value());
    }

    public function test_create_invoiced_status(): void
    {
        $status = OrderStatus::invoiced();

        $this->assertInstanceOf(OrderStatus::class, $status);
        $this->assertSame('invoiced', $status->value());
    }

    public function test_create_with_valid_value(): void
    {
        $status = OrderStatus::create('open');

        $this->assertInstanceOf(OrderStatus::class, $status);
        $this->assertSame('open', $status->value());
    }

    public function test_create_with_invalid_value_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        OrderStatus::create('invalid_status');
    }

    public function test_is_open(): void
    {
        $openStatus = OrderStatus::open();
        $cancelledStatus = OrderStatus::cancelled();

        $this->assertTrue($openStatus->isOpen());
        $this->assertFalse($cancelledStatus->isOpen());
    }

    public function test_is_cancelled(): void
    {
        $openStatus = OrderStatus::open();
        $cancelledStatus = OrderStatus::cancelled();

        $this->assertFalse($openStatus->isCancelled());
        $this->assertTrue($cancelledStatus->isCancelled());
    }

    public function test_is_invoiced(): void
    {
        $openStatus = OrderStatus::open();
        $invoicedStatus = OrderStatus::invoiced();

        $this->assertFalse($openStatus->isInvoiced());
        $this->assertTrue($invoicedStatus->isInvoiced());
    }
}
