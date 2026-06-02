<?php

namespace Tests\Unit\Cash\Domain\ValueObject;

use App\Cash\Domain\ValueObject\MovementType;
use PHPUnit\Framework\TestCase;

class MovementTypeTest extends TestCase
{
    public function test_create_with_valid_type_in(): void
    {
        $type = MovementType::create('in');

        $this->assertTrue($type->isIn());
        $this->assertFalse($type->isOut());
        $this->assertSame('in', $type->value());
    }

    public function test_create_with_valid_type_out(): void
    {
        $type = MovementType::create('out');

        $this->assertFalse($type->isIn());
        $this->assertTrue($type->isOut());
        $this->assertSame('out', $type->value());
    }

    public function test_in_named_constructor(): void
    {
        $type = MovementType::in();

        $this->assertTrue($type->isIn());
        $this->assertSame('in', $type->value());
    }

    public function test_out_named_constructor(): void
    {
        $type = MovementType::out();

        $this->assertTrue($type->isOut());
        $this->assertSame('out', $type->value());
    }

    public function test_create_with_invalid_type_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        MovementType::create('invalid');
    }

    public function test_equals(): void
    {
        $type1 = MovementType::in();
        $type2 = MovementType::in();
        $type3 = MovementType::out();

        $this->assertTrue($type1->equals($type2));
        $this->assertFalse($type1->equals($type3));
    }
}
