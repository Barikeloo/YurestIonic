<?php

declare(strict_types=1);

namespace App\Cash\Infrastructure\Persistence\Repositories;

use App\Cash\Domain\Entity\ZReport;
use App\Cash\Domain\Interfaces\ZReportRepositoryInterface;
use App\Cash\Infrastructure\Persistence\Models\EloquentZReport;
use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\Shared\Domain\ValueObject\Money;
use App\Shared\Domain\ValueObject\Uuid;

final class EloquentZReportRepository implements ZReportRepositoryInterface
{
    public function __construct(
        private readonly EloquentZReport $model,
    ) {}

    public function save(ZReport $zReport): void
    {
        $cashSessionInternalId = \App\Cash\Infrastructure\Persistence\Models\EloquentCashSession::query()
            ->where('uuid', $zReport->cashSessionId()->value())
            ->value('id');

        $restaurantId = \App\Cash\Infrastructure\Persistence\Models\EloquentCashSession::query()
            ->where('uuid', $zReport->cashSessionId()->value())
            ->value('restaurant_id');

        $this->model->newQuery()->updateOrCreate(
            ['uuid' => $zReport->id()->value()],
            [
                'restaurant_id' => $restaurantId,
                'cash_session_id' => $cashSessionInternalId,
                'report_number' => $zReport->reportNumber(),
                'report_hash' => $zReport->reportHash(),
                'total_sales_cents' => $zReport->totalSales()->toCents(),
                'total_cash_cents' => $zReport->totalCash()->toCents(),
                'total_card_cents' => $zReport->totalCard()->toCents(),
                'total_other_cents' => $zReport->totalOther()->toCents(),
                'cash_in_cents' => $zReport->cashIn()->toCents(),
                'cash_out_cents' => $zReport->cashOut()->toCents(),
                'tips_cents' => $zReport->tips()->toCents(),
                'discrepancy_cents' => $zReport->discrepancy()->toCents(),
                'sales_count' => $zReport->salesCount(),
                'cancelled_sales_count' => $zReport->cancelledSalesCount(),
                'generated_at' => $zReport->generatedAt()->value(),
            ],
        );
    }

    public function findByUuid(Uuid $uuid): ?ZReport
    {
        $model = $this->model->newQuery()->where('uuid', $uuid->value())->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findByCashSessionId(Uuid $cashSessionId): ?ZReport
    {
        $cashSessionInternalId = \App\Cash\Infrastructure\Persistence\Models\EloquentCashSession::query()
            ->where('uuid', $cashSessionId->value())
            ->value('id');

        if ($cashSessionInternalId === null) {
            return null;
        }

        $model = $this->model->newQuery()->where('cash_session_id', $cashSessionInternalId)->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function nextReportNumber(string $restaurantId): int
    {
        $restaurantInternalId = EloquentRestaurant::query()->where('uuid', $restaurantId)->value('id');
        $max = $this->model->newQuery()->where('restaurant_id', $restaurantInternalId)->max('report_number');

        return $max !== null ? (int) $max + 1 : 1;
    }

    private function toDomain(EloquentZReport $model): ZReport
    {
        return ZReport::generate(
            cashSessionId: Uuid::create($model->cashSession->uuid),
            reportNumber: $model->report_number,
            totalSales: Money::create($model->total_sales_cents),
            totalCash: Money::create($model->total_cash_cents),
            totalCard: Money::create($model->total_card_cents),
            totalOther: Money::create($model->total_other_cents),
            cashIn: Money::create($model->cash_in_cents),
            cashOut: Money::create($model->cash_out_cents),
            tips: Money::create($model->tips_cents),
            discrepancy: Money::create($model->discrepancy_cents),
            salesCount: $model->sales_count,
            cancelledSalesCount: $model->cancelled_sales_count,
        );
    }
}
