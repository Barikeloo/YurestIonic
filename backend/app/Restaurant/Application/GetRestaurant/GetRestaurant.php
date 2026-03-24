<?php

namespace App\Restaurant\Application\GetRestaurant;

use App\Restaurant\Domain\Interfaces\RestaurantRepositoryInterface;

final class GetRestaurant
{
    public function __construct(
        private readonly RestaurantRepositoryInterface $restaurantRepository,
    ) {}

    public function __invoke(string $id): ?GetRestaurantResponse
    {
        $restaurant = $this->restaurantRepository->getById($id);

        if ($restaurant === null) {
            return null;
        }

        return GetRestaurantResponse::create($restaurant);
    }
}
