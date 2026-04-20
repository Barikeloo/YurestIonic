<?php

namespace App\Restaurant\Application\DTO;

use App\Restaurant\Domain\Entity\Restaurant;

final readonly class RestaurantWithInternalId
{
    public function __construct(
        public Restaurant $restaurant,
        public int $internalId,
    ) {}
}
