<?php

namespace Tests\Unit\Menu\Domain\ValueObject;

use App\Menu\Domain\Exception\MenuInvalidConfigurationException;
use App\Menu\Domain\ValueObject\MenuValidity;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class MenuValidityTest extends TestCase
{
    public function test_always_named_constructor(): void
    {
        $validity = MenuValidity::always();

        $this->assertNull($validity->from());
        $this->assertNull($validity->to());
    }

    public function test_create_with_valid_range(): void
    {
        $from = new DateTimeImmutable('2026-01-01');
        $to = new DateTimeImmutable('2026-12-31');
        $validity = MenuValidity::create($from, $to);

        $this->assertEquals('2026-01-01', $validity->from()?->format('Y-m-d'));
        $this->assertEquals('2026-12-31', $validity->to()?->format('Y-m-d'));
    }

    public function test_create_with_only_from(): void
    {
        $from = new DateTimeImmutable('2026-01-01');
        $validity = MenuValidity::create($from, null);

        $this->assertEquals('2026-01-01', $validity->from()?->format('Y-m-d'));
        $this->assertNull($validity->to());
    }

    public function test_create_with_only_to(): void
    {
        $to = new DateTimeImmutable('2026-12-31');
        $validity = MenuValidity::create(null, $to);

        $this->assertNull($validity->from());
        $this->assertEquals('2026-12-31', $validity->to()?->format('Y-m-d'));
    }

    public function test_create_with_from_after_to_throws_exception(): void
    {
        $this->expectException(MenuInvalidConfigurationException::class);
        $this->expectExceptionMessage('Menu validity "from" date cannot be later than "to" date.');

        MenuValidity::create(
            new DateTimeImmutable('2026-12-31'),
            new DateTimeImmutable('2026-01-01'),
        );
    }

    public function test_create_normalizes_date_time(): void
    {
        $from = new DateTimeImmutable('2026-01-01 10:30:00');
        $validity = MenuValidity::create($from, null);

        $this->assertEquals('2026-01-01', $validity->from()?->format('Y-m-d'));
        $this->assertSame('00:00:00', $validity->from()?->format('H:i:s'));
    }

    public function test_is_valid_on_date_without_limits(): void
    {
        $validity = MenuValidity::always();

        $this->assertTrue($validity->isValidOnDate(new DateTimeImmutable('2026-06-15')));
        $this->assertTrue($validity->isValidOnDate(new DateTimeImmutable('2025-01-01')));
    }

    public function test_is_valid_on_date_with_from_only(): void
    {
        $validity = MenuValidity::create(new DateTimeImmutable('2026-06-01'), null);

        $this->assertTrue($validity->isValidOnDate(new DateTimeImmutable('2026-06-01')));
        $this->assertTrue($validity->isValidOnDate(new DateTimeImmutable('2026-07-01')));
        $this->assertFalse($validity->isValidOnDate(new DateTimeImmutable('2026-05-31')));
    }

    public function test_is_valid_on_date_with_to_only(): void
    {
        $validity = MenuValidity::create(null, new DateTimeImmutable('2026-06-30'));

        $this->assertTrue($validity->isValidOnDate(new DateTimeImmutable('2026-06-30')));
        $this->assertTrue($validity->isValidOnDate(new DateTimeImmutable('2026-06-15')));
        $this->assertFalse($validity->isValidOnDate(new DateTimeImmutable('2026-07-01')));
    }

    public function test_is_valid_on_date_with_full_range(): void
    {
        $validity = MenuValidity::create(
            new DateTimeImmutable('2026-01-01'),
            new DateTimeImmutable('2026-12-31'),
        );

        $this->assertTrue($validity->isValidOnDate(new DateTimeImmutable('2026-06-15')));
        $this->assertTrue($validity->isValidOnDate(new DateTimeImmutable('2026-01-01')));
        $this->assertTrue($validity->isValidOnDate(new DateTimeImmutable('2026-12-31')));
        $this->assertFalse($validity->isValidOnDate(new DateTimeImmutable('2025-12-31')));
        $this->assertFalse($validity->isValidOnDate(new DateTimeImmutable('2027-01-01')));
    }
}
