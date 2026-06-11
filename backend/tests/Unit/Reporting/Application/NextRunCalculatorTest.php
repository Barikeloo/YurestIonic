<?php

namespace Tests\Unit\Reporting\Application;

use App\Reporting\Application\Shared\NextRunCalculator;
use PHPUnit\Framework\TestCase;

class NextRunCalculatorTest extends TestCase
{
    private function calculatorAt(string $now): NextRunCalculator
    {
        return new NextRunCalculator(new \DateTimeImmutable($now));
    }

    // --- daily ---

    public function test_daily_returns_today_when_time_not_yet_passed(): void
    {
        $next = $this->calculatorAt('2026-06-11 06:00:00')->forDaily('08:00');

        $this->assertSame('2026-06-11 08:00', $next->format('Y-m-d H:i'));
    }

    public function test_daily_returns_tomorrow_when_time_already_passed(): void
    {
        $next = $this->calculatorAt('2026-06-11 09:00:00')->forDaily('08:00');

        $this->assertSame('2026-06-12 08:00', $next->format('Y-m-d H:i'));
    }

    public function test_daily_returns_tomorrow_when_exactly_at_time(): void
    {
        // target == now is not strictly greater, so it rolls to next day
        $next = $this->calculatorAt('2026-06-11 08:00:00')->forDaily('08:00');

        $this->assertSame('2026-06-12 08:00', $next->format('Y-m-d H:i'));
    }

    // --- weekly ---

    public function test_weekly_returns_later_this_week(): void
    {
        // 2026-06-11 is a Thursday (ISO weekday 4); target Saturday (6)
        $next = $this->calculatorAt('2026-06-11 06:00:00')->forWeekly(6, '08:00');

        $this->assertSame('2026-06-13 08:00', $next->format('Y-m-d H:i'));
        $this->assertSame('6', $next->format('N'));
    }

    public function test_weekly_same_day_before_time_returns_today(): void
    {
        // Thursday target, now Thursday before the hour
        $next = $this->calculatorAt('2026-06-11 06:00:00')->forWeekly(4, '08:00');

        $this->assertSame('2026-06-11 08:00', $next->format('Y-m-d H:i'));
    }

    public function test_weekly_same_day_after_time_returns_next_week(): void
    {
        // Thursday target, now Thursday after the hour -> +7 days
        $next = $this->calculatorAt('2026-06-11 09:00:00')->forWeekly(4, '08:00');

        $this->assertSame('2026-06-18 08:00', $next->format('Y-m-d H:i'));
        $this->assertSame('4', $next->format('N'));
    }

    public function test_weekly_earlier_weekday_rolls_to_next_week(): void
    {
        // now Thursday (4), target Monday (1) -> next Monday
        $next = $this->calculatorAt('2026-06-11 09:00:00')->forWeekly(1, '08:00');

        $this->assertSame('2026-06-15 08:00', $next->format('Y-m-d H:i'));
        $this->assertSame('1', $next->format('N'));
    }

    // --- monthly ---

    public function test_monthly_returns_later_this_month(): void
    {
        $next = $this->calculatorAt('2026-06-11 09:00:00')->forMonthly(20, '08:00');

        $this->assertSame('2026-06-20 08:00', $next->format('Y-m-d H:i'));
    }

    public function test_monthly_same_day_before_time_returns_today(): void
    {
        $next = $this->calculatorAt('2026-06-11 06:00:00')->forMonthly(11, '08:00');

        $this->assertSame('2026-06-11 08:00', $next->format('Y-m-d H:i'));
    }

    public function test_monthly_rolls_to_next_month_when_day_passed(): void
    {
        $next = $this->calculatorAt('2026-06-15 09:00:00')->forMonthly(10, '08:00');

        $this->assertSame('2026-07-10 08:00', $next->format('Y-m-d H:i'));
    }

    public function test_monthly_clamps_to_last_day_of_shorter_target_month(): void
    {
        // Day 30 requested, now late Jan -> February has 28 days in 2026
        $next = $this->calculatorAt('2026-01-31 09:00:00')->forMonthly(30, '08:00');

        $this->assertSame('2026-02-28 08:00', $next->format('Y-m-d H:i'));
    }

    public function test_monthly_rolls_from_december_to_january(): void
    {
        $next = $this->calculatorAt('2026-12-20 09:00:00')->forMonthly(10, '08:00');

        $this->assertSame('2027-01-10 08:00', $next->format('Y-m-d H:i'));
    }

    // --- quarterly ---

    public function test_quarterly_from_q1_returns_april_first(): void
    {
        $next = $this->calculatorAt('2026-02-15 09:00:00')->forQuarterly('08:00');

        $this->assertSame('2026-04-01 08:00', $next->format('Y-m-d H:i'));
    }

    public function test_quarterly_from_q2_returns_july_first(): void
    {
        $next = $this->calculatorAt('2026-05-15 09:00:00')->forQuarterly('08:00');

        $this->assertSame('2026-07-01 08:00', $next->format('Y-m-d H:i'));
    }

    public function test_quarterly_from_q3_returns_october_first(): void
    {
        $next = $this->calculatorAt('2026-08-15 09:00:00')->forQuarterly('08:00');

        $this->assertSame('2026-10-01 08:00', $next->format('Y-m-d H:i'));
    }

    public function test_quarterly_from_q4_returns_january_first_next_year(): void
    {
        $next = $this->calculatorAt('2026-11-15 09:00:00')->forQuarterly('08:00');

        $this->assertSame('2027-01-01 08:00', $next->format('Y-m-d H:i'));
    }
}
