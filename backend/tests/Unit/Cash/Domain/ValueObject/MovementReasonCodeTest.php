<?php

namespace Tests\Unit\Cash\Domain\ValueObject;

use App\Cash\Domain\ValueObject\MovementReasonCode;
use PHPUnit\Framework\TestCase;

class MovementReasonCodeTest extends TestCase
{
    public function test_create_with_valid_reason_code(): void
    {
        $code = MovementReasonCode::create('change_refill');

        $this->assertInstanceOf(MovementReasonCode::class, $code);
        $this->assertSame('change_refill', $code->value());
    }

    public function test_create_with_all_valid_reason_codes(): void
    {
        $this->assertSame('change_refill', MovementReasonCode::create('change_refill')->value());
        $this->assertSame('supplier_payment', MovementReasonCode::create('supplier_payment')->value());
        $this->assertSame('tip_declared', MovementReasonCode::create('tip_declared')->value());
        $this->assertSame('sangria', MovementReasonCode::create('sangria')->value());
        $this->assertSame('adjustment', MovementReasonCode::create('adjustment')->value());
        $this->assertSame('cancellation', MovementReasonCode::create('cancellation')->value());
        $this->assertSame('other', MovementReasonCode::create('other')->value());
    }

    public function test_change_refill_named_constructor(): void
    {
        $code = MovementReasonCode::changeRefill();

        $this->assertSame('change_refill', $code->value());
    }

    public function test_supplier_payment_named_constructor(): void
    {
        $code = MovementReasonCode::supplierPayment();

        $this->assertSame('supplier_payment', $code->value());
    }

    public function test_tip_declared_named_constructor(): void
    {
        $code = MovementReasonCode::tipDeclared();

        $this->assertSame('tip_declared', $code->value());
    }

    public function test_sangria_named_constructor(): void
    {
        $code = MovementReasonCode::sangria();

        $this->assertSame('sangria', $code->value());
    }

    public function test_adjustment_named_constructor(): void
    {
        $code = MovementReasonCode::adjustment();

        $this->assertSame('adjustment', $code->value());
    }

    public function test_cancellation_named_constructor(): void
    {
        $code = MovementReasonCode::cancellation();

        $this->assertSame('cancellation', $code->value());
    }

    public function test_other_named_constructor(): void
    {
        $code = MovementReasonCode::other();

        $this->assertSame('other', $code->value());
    }

    public function test_create_with_invalid_code_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        MovementReasonCode::create('invalid');
    }

    public function test_equals(): void
    {
        $code1 = MovementReasonCode::create('change_refill');
        $code2 = MovementReasonCode::create('change_refill');
        $code3 = MovementReasonCode::create('other');

        $this->assertTrue($code1->equals($code2));
        $this->assertFalse($code1->equals($code3));
    }
}
