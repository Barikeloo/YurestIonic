<?php

namespace App\Restaurant\Application\ListRestaurants;

use App\Restaurant\Domain\Interfaces\RestaurantRepositoryInterface;

final class ListRestaurants
{
    public function __construct(
        private readonly RestaurantRepositoryInterface $restaurantRepository,
    ) {}

    public function __invoke(): array
    {
        $restaurants = $this->restaurantRepository->all();

        return array_map(
            static fn ($restaurant): array => ListRestaurantsResponse::create($restaurant)->toArray(),
            $restaurants,
        );
    }
}
