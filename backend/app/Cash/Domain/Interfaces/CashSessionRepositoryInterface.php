<?php

declare(strict_types=1);

namespace App\Cash\Domain\Interfaces;

use App\Cash\Domain\Entity\CashSession;
use App\Cash\Domain\ValueObject\DeviceId;
use App\Shared\Domain\ValueObject\Uuid;

interface CashSessionRepositoryInterface
{
    public function save(CashSession $cashSession): void;

    public function findByUuid(Uuid $uuid): ?CashSession;

    public function findActiveByDeviceId(DeviceId $deviceId, Uuid $restaurantId): ?CashSession;

    public function findByRestaurantId(Uuid $restaurantId): array;

    public function findClosedByRestaurantId(Uuid $restaurantId): array;

    public function findLastClosedByRestaurant(Uuid $restaurantId): ?CashSession;

    public function findOrphanByRestaurant(Uuid $restaurantId): ?CashSession;

    public function delete(Uuid $id): void;
}
