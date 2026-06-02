<?php

namespace Tests\Unit\Audit\Domain\ValueObject;

use App\Audit\Domain\ValueObject\ActionSlug;
use PHPUnit\Framework\TestCase;

class ActionSlugTest extends TestCase
{
    public function test_create_with_valid_slug(): void
    {
        $slug = ActionSlug::create('caja.opened');

        $this->assertSame('caja.opened', $slug->value());
    }

    public function test_create_with_nested_module_name(): void
    {
        $slug = ActionSlug::create('auth.login_pin_failed');

        $this->assertSame('auth.login_pin_failed', $slug->value());
    }

    public function test_create_with_multi_word_action(): void
    {
        $slug = ActionSlug::create('caja.z_report_generated');

        $this->assertSame('caja.z_report_generated', $slug->value());
    }

    public function test_create_with_invalid_format_no_dot_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid audit action slug');

        ActionSlug::create('invalid');
    }

    public function test_create_with_uppercase_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ActionSlug::create('Caja.Opened');
    }

    public function test_create_with_empty_string_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ActionSlug::create('');
    }

    public function test_module_extracts_first_part(): void
    {
        $slug = ActionSlug::create('order.line_added');

        $this->assertSame('order', $slug->module());
    }

    public function test_equals(): void
    {
        $slug1 = ActionSlug::create('caja.opened');
        $slug2 = ActionSlug::create('caja.opened');
        $slug3 = ActionSlug::create('caja.closed');

        $this->assertTrue($slug1->equals($slug2));
        $this->assertFalse($slug1->equals($slug3));
    }
}
