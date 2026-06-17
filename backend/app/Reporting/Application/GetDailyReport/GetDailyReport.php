<?php

declare(strict_types=1);

namespace App\Reporting\Application\GetDailyReport;

use App\Reporting\Application\Shared\DateRange;
use App\Reporting\Domain\ReadModel\DashboardReadRepositoryInterface;
use App\Reporting\Domain\ReadModel\RestaurantInfoReadRepositoryInterface;

final readonly class GetDailyReport
{
    public function __construct(
        private DashboardReadRepositoryInterface $dashboard,
        private RestaurantInfoReadRepositoryInterface $restaurantInfo,
    ) {}

    public function __invoke(GetDailyReportCommand $command): GetDailyReportResponse
    {
        $range      = DateRange::fromPeriod($command->period);
        $data       = $this->dashboard->getDashboardData($command->restaurantId, $range);
        $restaurant = $this->restaurantInfo->getRestaurantInfo($command->restaurantId);

        return GetDailyReportResponse::create(
            restaurant:      $restaurant,
            periodLabel:     $range->label,
            kpis:            $this->buildKpis($data),
            byFamily:        $data['by_family'] ?? [],
            topProducts:     $data['top_products'] ?? [],
            byPaymentMethod: $data['by_payment_method'] ?? [],
        );
    }

    private function buildKpis(array $data): array
    {
        $revenue   = (int) ($data['revenue'] ?? 0);
        $tickets   = (int) ($data['tickets'] ?? 0);
        $avgTicket = $tickets > 0 ? intdiv($revenue, $tickets) : 0;
        $itemsSold = (int) ($data['items_sold'] ?? 0);
        $diners    = (int) ($data['diners'] ?? 0);

        $prevRevenue   = (int) ($data['prev_revenue'] ?? 0);
        $prevTickets   = (int) ($data['prev_tickets'] ?? 0);
        $prevAvgTicket = $prevTickets > 0 ? intdiv($prevRevenue, $prevTickets) : 0;
        $prevItemsSold = (int) ($data['prev_items_sold'] ?? 0);
        $prevDiners    = (int) ($data['prev_diners'] ?? 0);

        return [
            'revenue'    => $this->metric($revenue,   $prevRevenue),
            'tickets'    => $this->metric($tickets,   $prevTickets),
            'avg_ticket' => $this->metric($avgTicket, $prevAvgTicket),
            'items_sold' => $this->metric($itemsSold, $prevItemsSold),
            'diners'     => $this->metric($diners,    $prevDiners),
        ];
    }

    private function metric(int $v, int $prev): array
    {
        $deltaPct = $prev > 0 ? round(($v - $prev) / $prev * 100, 1) : 0.0;

        return ['v' => $v, 'prev' => $prev, 'delta_pct' => $deltaPct];
    }
}
