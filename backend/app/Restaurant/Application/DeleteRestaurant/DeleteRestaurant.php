<?php

namespace App\Restaurant\Application\DeleteRestaurant;

use App\Restaurant\Domain\Interfaces\RestaurantRepositoryInterface;

final class DeleteRestaurant
{
    public function __construct(
        private readonly RestaurantRepositoryInterface $restaurantRepository,
    ) {}

    public function __invoke(string $id): bool
    {
        $restaurant = $this->restaurantRepository->getById($id);

        if ($restaurant === null) {
            return false;
        }

        $this->restaurantRepository->delete($restaurant->getId());

        return true;
    }
}
