<?php

namespace Tests\Unit\Menu\Domain\ValueObject;

use App\Menu\Domain\Exception\MenuInvalidConfigurationException;
use App\Menu\Domain\ValueObject\MenuAvailability;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class MenuAvailabilityTest extends TestCase
{
    public function test_always_available(): void
    {
        $availability = MenuAvailability::alwaysAvailable();

        $this->assertSame(127, $availability->daysBitmask());
        $this->assertNull($availability->fromTime());
        $this->assertNull($availability->toTime());
        $this->assertTrue($availability->isAllDay());
    }

    public function test_create_with_valid_bitmask(): void
    {
        $availability = MenuAvailability::create(42, null, null);

        $this->assertSame(42, $availability->daysBitmask());
    }

    public function test_create_with_bitmask_zero(): void
    {
        $availability = MenuAvailability::create(0, null, null);

        $this->assertSame(0, $availability->daysBitmask());
        $this->assertTrue($availability->isAllDay());
    }

    public function test_create_with_negative_bitmask_throws_exception(): void
    {
        $this->expectException(MenuInvalidConfigurationException::class);
        $this->expectExceptionMessage('Invalid menu availability days bitmask: -1');

        MenuAvailability::create(-1, null, null);
    }

    public function test_create_with_bitmask_over_127_throws_exception(): void
    {
        $this->expectException(MenuInvalidConfigurationException::class);
        $this->expectExceptionMessage('Invalid menu availability days bitmask: 128');

        MenuAvailability::create(128, null, null);
    }

    public function test_create_with_partial_time_range_throws_exception(): void
    {
        $this->expectException(MenuInvalidConfigurationException::class);
        $this->expectExceptionMessage('Menu availability time range must have both "from" and "to" or neither.');

        MenuAvailability::create(127, '10:00', null);
    }

    public function test_create_with_from_after_to_throws_exception(): void
    {
        $this->expectException(MenuInvalidConfigurationException::class);
        $this->expectExceptionMessage('Menu availability "from" time must be earlier than "to" time.');

        MenuAvailability::create(127, '18:00', '10:00');
    }

    public function test_create_with_equal_times_throws_exception(): void
    {
        $this->expectException(MenuInvalidConfigurationException::class);
        $this->expectExceptionMessage('Menu availability "from" time must be earlier than "to" time.');

        MenuAvailability::create(127, '10:00', '10:00');
    }

    public function test_create_normalizes_time_format(): void
    {
        $availability = MenuAvailability::create(127, '10:00', '14:00');

        $this->assertSame('10:00:00', $availability->fromTime());
        $this->assertSame('14:00:00', $availability->toTime());
    }

    public function test_create_with_seconds_already_normalized(): void
    {
        $availability = MenuAvailability::create(127, '10:00:00', '14:00:00');

        $this->assertSame('10:00:00', $availability->fromTime());
        $this->assertSame('14:00:00', $availability->toTime());
    }

    public function test_create_with_invalid_time_format_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        MenuAvailability::create(127, '10-00', '14-00');
    }

    public function test_is_available_on_weekday(): void
    {
        $availability = MenuAvailability::create(
            0b00001010, // Martes (bit 1) y Jueves (bit 3): 0b00001010 = 10
            null,
            null,
        );

        $this->assertTrue($availability->isAvailableOnWeekday(2)); // Martes
        $this->assertTrue($availability->isAvailableOnWeekday(4)); // Jueves
        $this->assertFalse($availability->isAvailableOnWeekday(1)); // Lunes
        $this->assertFalse($availability->isAvailableOnWeekday(3)); // Miércoles
        $this->assertFalse($availability->isAvailableOnWeekday(5)); // Viernes
        $this->assertFalse($availability->isAvailableOnWeekday(6)); // Sábado
        $this->assertFalse($availability->isAvailableOnWeekday(7)); // Domingo
    }

    public function test_is_available_on_weekday_with_invalid_day_throws_exception(): void
    {
        $availability = MenuAvailability::alwaysAvailable();

        $this->expectException(\InvalidArgumentException::class);

        $availability->isAvailableOnWeekday(0);
    }

    public function test_is_available_at_with_all_days(): void
    {
        $availability = MenuAvailability::alwaysAvailable();

        $this->assertTrue($availability->isAvailableAt(new DateTimeImmutable('2026-06-15 10:00:00')));
        $this->assertTrue($availability->isAvailableAt(new DateTimeImmutable('2026-06-15 23:59:59')));
    }

    public function test_is_available_at_with_time_range(): void
    {
        $availability = MenuAvailability::create(127, '10:00', '14:00');

        $this->assertTrue($availability->isAvailableAt(new DateTimeImmutable('2026-06-15 10:00:00')));
        $this->assertTrue($availability->isAvailableAt(new DateTimeImmutable('2026-06-15 13:59:59')));
        $this->assertFalse($availability->isAvailableAt(new DateTimeImmutable('2026-06-15 09:59:59')));
        $this->assertFalse($availability->isAvailableAt(new DateTimeImmutable('2026-06-15 14:00:00')));
    }

    public function test_is_available_at_respects_weekday(): void
    {
        // Solo disponible en Lunes (bit 0)
        $availability = MenuAvailability::create(0b0000001, null, null);

        // 2026-06-15 es Lunes
        $this->assertTrue($availability->isAvailableAt(new DateTimeImmutable('2026-06-15 10:00:00')));
        // 2026-06-16 es Martes
        $this->assertFalse($availability->isAvailableAt(new DateTimeImmutable('2026-06-16 10:00:00')));
    }

    public function test_is_available_at_combines_weekday_and_time(): void
    {
        // Solo disponible en Lunes de 10:00 a 14:00
        $availability = MenuAvailability::create(0b0000001, '10:00', '14:00');

        // Lunes dentro del rango
        $this->assertTrue($availability->isAvailableAt(new DateTimeImmutable('2026-06-15 11:00:00')));
        // Lunes fuera del rango
        $this->assertFalse($availability->isAvailableAt(new DateTimeImmutable('2026-06-15 09:00:00')));
        // Martes dentro del rango (día incorrecto)
        $this->assertFalse($availability->isAvailableAt(new DateTimeImmutable('2026-06-16 11:00:00')));
    }

    public function test_not_all_day_when_time_range_set(): void
    {
        $availability = MenuAvailability::create(127, '10:00', '14:00');

        $this->assertFalse($availability->isAllDay());
    }
}
