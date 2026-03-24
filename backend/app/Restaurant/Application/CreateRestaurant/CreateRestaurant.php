<?php

namespace App\Restaurant\Application\CreateRestaurant;

use App\Restaurant\Domain\Entity\Restaurant;
use App\Restaurant\Domain\Interfaces\RestaurantRepositoryInterface;
use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\Uuid;

final class CreateRestaurant
{
    public function __construct(
        private readonly RestaurantRepositoryInterface $restaurantRepository,
    ) {}

    public function __invoke(
        string $name,
        ?string $legalName,
        ?string $taxId,
        string $email,
        string $password,
    ): CreateRestaurantResponse {
        $restaurant = Restaurant::dddCreate(
            id: Uuid::generate(),
            name: $name,
            legalName: $legalName,
            taxId: $taxId,
            email: Email::create($email),
            password: $password, // TODO: hashear la contraseña
        );

        $this->restaurantRepository->save($restaurant);

        return CreateRestaurantResponse::create($restaurant);
    }
}
