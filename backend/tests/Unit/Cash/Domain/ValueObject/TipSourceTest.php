<?php

namespace Tests\Unit\Cash\Domain\ValueObject;

use App\Cash\Domain\ValueObject\TipSource;
use PHPUnit\Framework\TestCase;

class TipSourceTest extends TestCase
{
    public function test_create_with_valid_source_card_added(): void
    {
        $source = TipSource::create('card_added');

        $this->assertTrue($source->isCardAdded());
        $this->assertFalse($source->isCashDeclared());
        $this->assertSame('card_added', $source->value());
    }

    public function test_create_with_valid_source_cash_declared(): void
    {
        $source = TipSource::create('cash_declared');

        $this->assertFalse($source->isCardAdded());
        $this->assertTrue($source->isCashDeclared());
        $this->assertSame('cash_declared', $source->value());
    }

    public function test_card_added_named_constructor(): void
    {
        $source = TipSource::cardAdded();

        $this->assertTrue($source->isCardAdded());
        $this->assertSame('card_added', $source->value());
    }

    public function test_cash_declared_named_constructor(): void
    {
        $source = TipSource::cashDeclared();

        $this->assertTrue($source->isCashDeclared());
        $this->assertSame('cash_declared', $source->value());
    }

    public function test_create_with_invalid_source_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        TipSource::create('invalid');
    }

    public function test_equals(): void
    {
        $source1 = TipSource::cardAdded();
        $source2 = TipSource::cardAdded();
        $source3 = TipSource::cashDeclared();

        $this->assertTrue($source1->equals($source2));
        $this->assertFalse($source1->equals($source3));
    }
}
