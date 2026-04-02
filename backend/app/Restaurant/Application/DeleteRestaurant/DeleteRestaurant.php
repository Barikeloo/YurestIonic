<?php

namespace App\Restaurant\Application\DeleteRestaurant;

use App\Restaurant\Domain\Interfaces\RestaurantCascadeDeleteInterface;
use App\Restaurant\Domain\Interfaces\RestaurantRepositoryInterface;

final class DeleteRestaurant
{
    public function __construct(
        private readonly RestaurantRepositoryInterface $restaurantRepository,
        private readonly RestaurantCascadeDeleteInterface $restaurantCascadeDelete,
    ) {}

    public function __invoke(string $id): bool
    {
        $restaurant = $this->restaurantRepository->getById($id);

        if ($restaurant === null) {
            return false;
        }

        return $this->restaurantCascadeDelete->deleteByRestaurantUuid($id);
    }
}
