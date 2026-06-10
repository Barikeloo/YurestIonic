<?php

declare(strict_types=1);

namespace App\Reporting\Application\Shared;

final readonly class DateRange
{
    public function __construct(
        public \DateTimeImmutable $from,
        public \DateTimeImmutable $to,
        public \DateTimeImmutable $prevFrom,
        public \DateTimeImmutable $prevTo,
        public string             $label,
    ) {}

    public static function fromPeriod(string $period): self
    {
        $now   = new \DateTimeImmutable('now');
        $today = new \DateTimeImmutable('today midnight');

        return match ($period) {
            'today' => new self(
                from:     $today,
                to:       $now,
                prevFrom: $today->modify('-1 day'),
                prevTo:   $now->modify('-1 day'),
                label:    'Hoy · ' . self::fmtDate($today),
            ),
            'yesterday' => new self(
                from:     $today->modify('-1 day'),
                to:       $today->modify('-1 second'),
                prevFrom: $today->modify('-2 days'),
                prevTo:   $today->modify('-1 day')->modify('-1 second'),
                label:    'Ayer · ' . self::fmtDate($today->modify('-1 day')),
            ),
            'week' => (static function () use ($today, $now): self {
                $dow  = (int) $today->format('N') - 1;
                $from = $today->modify("-{$dow} days");
                return new self(
                    from:     $from,
                    to:       $now,
                    prevFrom: $from->modify('-7 days'),
                    prevTo:   $now->modify('-7 days'),
                    label:    'Esta semana · ' . self::fmtDate($from) . ' – ' . self::fmtDate($today),
                );
            })(),
            'month' => new self(
                from:     new \DateTimeImmutable('first day of this month midnight'),
                to:       $now,
                prevFrom: new \DateTimeImmutable('first day of last month midnight'),
                prevTo:   new \DateTimeImmutable('last day of last month 23:59:59'),
                label:    'Este mes · ' . self::fmtMonth($today),
            ),
            default => throw new \InvalidArgumentException("Invalid period: {$period}"),
        };
    }

    public static function forQuarter(int $year, string $quarter): self
    {
        $ranges = [
            'T1' => ['01-01', '03-31', 'T1 · ene-mar'],
            'T2' => ['04-01', '06-30', 'T2 · abr-jun'],
            'T3' => ['07-01', '09-30', 'T3 · jul-sep'],
            'T4' => ['10-01', '12-31', 'T4 · oct-dic'],
        ];

        if (!isset($ranges[$quarter])) {
            throw new \InvalidArgumentException("Invalid quarter: {$quarter}");
        }

        [$startMd, $endMd, $label] = $ranges[$quarter];

        $from = new \DateTimeImmutable("{$year}-{$startMd} 00:00:00");
        $to   = new \DateTimeImmutable("{$year}-{$endMd} 23:59:59");

        return new self(
            from:     $from,
            to:       $to,
            prevFrom: $from->modify('-1 year'),
            prevTo:   $to->modify('-1 year'),
            label:    "{$label} {$year}",
        );
    }

    public static function currentQuarter(): string
    {
        $month = (int) (new \DateTimeImmutable('now'))->format('n');
        return match (true) {
            $month <= 3  => 'T1',
            $month <= 6  => 'T2',
            $month <= 9  => 'T3',
            default      => 'T4',
        };
    }

    public static function quarterElapsedPct(int $year, string $quarter): int
    {
        $range = self::forQuarter($year, $quarter);
        $now   = new \DateTimeImmutable('now');

        if ($now < $range->from) {
            return 0;
        }
        if ($now > $range->to) {
            return 100;
        }

        $total   = $range->to->getTimestamp() - $range->from->getTimestamp();
        $elapsed = $now->getTimestamp()        - $range->from->getTimestamp();

        return (int) round(($elapsed / $total) * 100);
    }

    private static function fmtDate(\DateTimeImmutable $d): string
    {
        $months = ['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];
        return $d->format('j') . ' ' . $months[(int) $d->format('n') - 1] . ' ' . $d->format('Y');
    }

    private static function fmtMonth(\DateTimeImmutable $d): string
    {
        $months = ['enero','febrero','marzo','abril','mayo','junio',
                   'julio','agosto','septiembre','octubre','noviembre','diciembre'];
        return $months[(int) $d->format('n') - 1] . ' ' . $d->format('Y');
    }
}
