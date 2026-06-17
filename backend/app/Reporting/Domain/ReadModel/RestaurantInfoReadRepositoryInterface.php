<?php

declare(strict_types=1);

namespace App\Reporting\Domain\ReadModel;

interface RestaurantInfoReadRepositoryInterface
{
    public function getRestaurantInfo(int $restaurantId): array;
}
