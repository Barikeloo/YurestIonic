<?php

namespace App\Restaurant\Application\UpdateRestaurant;

use App\Restaurant\Domain\Interfaces\RestaurantRepositoryInterface;
use App\Shared\Domain\ValueObject\Email;

final class UpdateRestaurant
{
    public function __construct(
        private readonly RestaurantRepositoryInterface $restaurantRepository,
    ) {}

    public function __invoke(
        string $id,
        ?string $name = null,
        ?string $legalName = null,
        ?string $taxId = null,
        ?string $email = null,
    ): ?UpdateRestaurantResponse {
        $restaurant = $this->restaurantRepository->getById($id);

        if ($restaurant === null) {
            return null;
        }

        if ($name !== null) {
            $restaurant->updateName($name);
        }

        if ($legalName !== null) {
            $restaurant->updateLegalName($legalName);
        }

        if ($taxId !== null) {
            $restaurant->updateTaxId($taxId);
        }

        if ($email !== null) {
            $restaurant->updateEmail(Email::create($email));
        }

        $this->restaurantRepository->save($restaurant);

        return UpdateRestaurantResponse::create($restaurant);
    }
}
