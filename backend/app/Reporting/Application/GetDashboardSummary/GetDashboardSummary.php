<?php

declare(strict_types=1);

namespace App\Reporting\Application\GetDashboardSummary;

use App\Reporting\Application\Shared\DateRange;
use App\Reporting\Domain\Interfaces\ReportingRepositoryInterface;

final readonly class GetDashboardSummary
{
    public function __construct(
        private ReportingRepositoryInterface $repository,
    ) {}

    public function __invoke(GetDashboardSummaryCommand $command): GetDashboardSummaryResponse
    {
        $range = DateRange::fromPeriod($command->period);
        $data  = $this->repository->getDashboardData($command->restaurantId, $range);

        return $this->buildResponse($command->period, $range, $data);
    }

    private function buildResponse(string $period, DateRange $range, array $data): GetDashboardSummaryResponse
    {
        $revenue   = (int) $data['revenue'];
        $tickets   = (int) $data['tickets'];
        $avgTicket = $tickets > 0 ? intdiv($revenue, $tickets) : 0;
        $itemsSold = (int) $data['items_sold'];
        $diners    = (int) $data['diners'];

        $prevRevenue   = (int) $data['prev_revenue'];
        $prevTickets   = (int) $data['prev_tickets'];
        $prevAvgTicket = $prevTickets > 0 ? intdiv($prevRevenue, $prevTickets) : 0;
        $prevItemsSold = (int) $data['prev_items_sold'];
        $prevDiners    = (int) $data['prev_diners'];

        $kpis = [
            'revenue'    => $this->kpiMetric($revenue,   $prevRevenue),
            'tickets'    => $this->kpiMetric($tickets,   $prevTickets),
            'avg_ticket' => $this->kpiMetric($avgTicket, $prevAvgTicket),
            'items_sold' => $this->kpiMetric($itemsSold, $prevItemsSold),
            'diners'     => $this->kpiMetric($diners,    $prevDiners),
        ];

        return GetDashboardSummaryResponse::create(
            period:          $period,
            dateLabel:       $range->label,
            kpis:            $kpis,
            sparks:          $this->buildSparks($data['day_totals'], $data['day_items']),
            byHour:          $this->fillHours($data['by_hour']),
            byHourPrev:      $this->fillHours($data['by_hour_prev']),
            byDay:           $this->buildByDay($data['day_totals']),
            byFamily:        $data['by_family'],
            topProducts:     $data['top_products'],
            byPaymentMethod: $data['by_payment_method'],
        );
    }

    private function kpiMetric(int $v, int $prev): array
    {
        $deltaPct = $prev > 0 ? round(($v - $prev) / $prev * 100, 1) : 0.0;
        return ['v' => $v, 'prev' => $prev, 'delta_pct' => $deltaPct];
    }

    private function fillHours(array $hourRows): array
    {
        $indexed = [];
        foreach ($hourRows as $row) {
            $indexed[(int) $row['h']] = $row;
        }

        $result = [];
        for ($h = 8; $h <= 23; $h++) {
            $result[] = [
                'l' => str_pad((string) $h, 2, '0', STR_PAD_LEFT),
                'v' => isset($indexed[$h]) ? (int) $indexed[$h]['v'] : 0,
                'n' => isset($indexed[$h]) ? (int) $indexed[$h]['n'] : 0,
            ];
        }
        return $result;
    }

    private function buildSparks(array $dayTotals, array $dayItems): array
    {
        $today = new \DateTimeImmutable('today');
        $revArr = $tixArr = $avgArr = $itmArr = [];

        for ($i = 13; $i >= 0; $i--) {
            $date     = $today->modify("-{$i} days")->format('Y-m-d');
            $rev      = (int) ($dayTotals[$date]['v'] ?? 0);
            $tix      = (int) ($dayTotals[$date]['n'] ?? 0);
            $itm      = (int) ($dayItems[$date]       ?? 0);
            $revArr[] = $rev;
            $tixArr[] = $tix;
            $avgArr[] = $tix > 0 ? intdiv($rev, $tix) : 0;
            $itmArr[] = $itm;
        }

        return [
            'revenue'    => $revArr,
            'tickets'    => $tixArr,
            'avg_ticket' => $avgArr,
            'items'      => $itmArr,
        ];
    }

    private function buildByDay(array $dayTotals): array
    {
        $months = ['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];
        $today  = new \DateTimeImmutable('today');
        $result = [];

        for ($i = 13; $i >= 0; $i--) {
            $d      = $today->modify("-{$i} days");
            $date   = $d->format('Y-m-d');
            $label  = $d->format('j') . ' ' . $months[(int) $d->format('n') - 1];
            $result[] = [
                'l' => $label,
                'v' => (int) ($dayTotals[$date]['v'] ?? 0),
                'n' => (int) ($dayTotals[$date]['n'] ?? 0),
            ];
        }

        return $result;
    }
}
