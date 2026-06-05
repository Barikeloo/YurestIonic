<?php

declare(strict_types=1);

namespace App\Audit\Infrastructure\Persistence\Repositories;

use App\Audit\Domain\Interfaces\VerifyChainResultRepositoryInterface;
use App\Audit\Domain\ValueObject\VerifyChainResult;
use App\Audit\Infrastructure\Persistence\Models\EloquentVerifyChainResult;
use App\Shared\Domain\ValueObject\Uuid;
use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;

final class EloquentVerifyChainResultRepository implements VerifyChainResultRepositoryInterface
{
    public function save(VerifyChainResult $result): void
    {
        $model = new EloquentVerifyChainResult();
        $model->restaurant_id = $this->resolveRestaurantId($result->restaurantId);
        $model->is_valid = $result->isValid;
        $model->total_events = $result->totalEvents;
        $model->verified_count = $result->verifiedCount;
        $model->broken_events = $result->brokenEvents;
        $model->first_broken_index = $result->firstBrokenIndex;
        $model->verified_at = $result->verifiedAt;
        $model->save();
    }

    public function latestByRestaurant(Uuid $restaurantId): ?VerifyChainResult
    {
        $row = EloquentVerifyChainResult::query()
            ->where('restaurant_id', $this->resolveRestaurantId($restaurantId))
            ->orderByDesc('verified_at')
            ->first();

        if ($row === null) {
            return null;
        }

        return new VerifyChainResult(
            restaurantId: $restaurantId,
            isValid: $row->is_valid,
            totalEvents: $row->total_events,
            verifiedCount: $row->verified_count,
            brokenEvents: $row->broken_events ?? [],
            firstBrokenIndex: $row->first_broken_index,
            verifiedAt: new \DateTimeImmutable((string) $row->verified_at),
        );
    }

    private function resolveRestaurantId(Uuid $uuid): int
    {
        return (int) EloquentRestaurant::query()
            ->where('uuid', $uuid->value())
            ->value('id');
    }
}
