<?php

declare(strict_types=1);

namespace App\Sale\Infrastructure\Persistence\Repositories;

use App\Sale\Domain\Entity\ChargeSessionLineAssignment;
use App\Sale\Domain\Interfaces\ChargeSessionLineAssignmentRepositoryInterface;
use App\Sale\Infrastructure\Persistence\Models\ChargeSessionLineAssignmentModel;
use App\Shared\Domain\ValueObject\Uuid;
use Illuminate\Support\Facades\DB;

final class EloquentChargeSessionLineAssignmentRepository implements ChargeSessionLineAssignmentRepositoryInterface
{
    public function findBySessionId(Uuid $chargeSessionId): array
    {
        $models = ChargeSessionLineAssignmentModel::query()
            ->where('charge_session_id', $chargeSessionId->value())
            ->orderBy('created_at')
            ->get();

        return $models
            ->map(fn (ChargeSessionLineAssignmentModel $m): ChargeSessionLineAssignment => $this->toEntity($m))
            ->all();
    }

    public function replaceForSession(Uuid $chargeSessionId, array $assignments): void
    {
        DB::transaction(function () use ($chargeSessionId, $assignments): void {
            ChargeSessionLineAssignmentModel::query()
                ->where('charge_session_id', $chargeSessionId->value())
                ->delete();

            foreach ($assignments as $assignment) {
                ChargeSessionLineAssignmentModel::create([
                    'id' => $assignment->id()->value(),
                    'charge_session_id' => $assignment->chargeSessionId()->value(),
                    'order_line_id' => $assignment->orderLineId()->value(),
                    'diner_number' => $assignment->dinerNumber(),
                ]);
            }
        });
    }

    public function deleteByOrderLineIds(Uuid $chargeSessionId, array $orderLineIds): void
    {
        if (count($orderLineIds) === 0) {
            return;
        }

        $values = array_map(static fn (Uuid $id): string => $id->value(), $orderLineIds);

        ChargeSessionLineAssignmentModel::query()
            ->where('charge_session_id', $chargeSessionId->value())
            ->whereIn('order_line_id', $values)
            ->delete();
    }

    private function toEntity(ChargeSessionLineAssignmentModel $model): ChargeSessionLineAssignment
    {
        return ChargeSessionLineAssignment::fromPersistence(
            id: (string) $model->id,
            chargeSessionId: (string) $model->charge_session_id,
            orderLineId: (string) $model->order_line_id,
            dinerNumber: (int) $model->diner_number,
            createdAt: $model->created_at->toDateTimeImmutable(),
            updatedAt: $model->updated_at->toDateTimeImmutable(),
        );
    }
}
