<?php

declare(strict_types=1);

namespace App\Cash\Infrastructure\Persistence\Repositories;

use App\Cash\Domain\Entity\ZReport;
use App\Cash\Domain\Interfaces\ZReportRepositoryInterface;
use App\Cash\Infrastructure\Persistence\Models\EloquentCashSession;
use App\Cash\Infrastructure\Persistence\Models\EloquentZReport;
use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\Shared\Domain\ValueObject\Uuid;

final class EloquentZReportRepository implements ZReportRepositoryInterface
{
    public function __construct(
        private readonly EloquentZReport $model,
    ) {}

    public function save(ZReport $zReport): void
    {
        $restaurantId = EloquentRestaurant::query()
            ->where('uuid', $zReport->restaurantId()->value())
            ->value('id');

        $cashSessionInternalId = EloquentCashSession::query()
            ->where('uuid', $zReport->cashSessionId()->value())
            ->value('id');

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
        $cashSessionInternalId = EloquentCashSession::query()
            ->where('uuid', $cashSessionId->value())
            ->value('id');

        if ($cashSessionInternalId === null) {
            return null;
        }

        $model = $this->model->newQuery()
            ->where('cash_session_id', $cashSessionInternalId)
            ->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function nextReportNumber(Uuid $restaurantId): int
    {
        $restaurantInternalId = EloquentRestaurant::query()
            ->where('uuid', $restaurantId->value())
            ->value('id');

        $max = $this->model->newQuery()
            ->where('restaurant_id', $restaurantInternalId)
            ->max('report_number');

        return $max !== null ? (int) $max + 1 : 1;
    }

    private function toDomain(EloquentZReport $model): ZReport
    {
        $restaurantUuid = EloquentRestaurant::query()
            ->where('id', $model->restaurant_id)
            ->value('uuid');

        $cashSessionUuid = EloquentCashSession::query()
            ->where('id', $model->cash_session_id)
            ->value('uuid');

        return ZReport::fromPersistence(
            $model->uuid,
            $restaurantUuid,
            $cashSessionUuid,
            (int) $model->report_number,
            $model->report_hash,
            (int) $model->total_sales_cents,
            (int) $model->total_cash_cents,
            (int) $model->total_card_cents,
            (int) $model->total_other_cents,
            (int) $model->cash_in_cents,
            (int) $model->cash_out_cents,
            (int) $model->tips_cents,
            (int) $model->discrepancy_cents,
            (int) $model->sales_count,
            (int) $model->cancelled_sales_count,
            $model->generated_at->toDateTimeImmutable(),
        );
    }
}
