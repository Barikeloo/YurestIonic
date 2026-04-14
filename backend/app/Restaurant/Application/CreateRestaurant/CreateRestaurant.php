<?php

namespace App\Restaurant\Application\CreateRestaurant;

use App\Restaurant\Domain\Entity\Restaurant;
use App\Restaurant\Domain\Interfaces\RestaurantRepositoryInterface;
use App\Restaurant\Domain\ValueObject\RestaurantLegalName;
use App\Restaurant\Domain\ValueObject\RestaurantName;
use App\Restaurant\Domain\ValueObject\RestaurantPasswordHash;
use App\Restaurant\Domain\ValueObject\RestaurantTaxId;
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
            name: RestaurantName::create($name),
            legalName: RestaurantLegalName::createNullable($legalName),
            taxId: RestaurantTaxId::createNullable($taxId),
            email: Email::create($email),
            password: RestaurantPasswordHash::create($password),
        );

        $this->restaurantRepository->save($restaurant);

        return CreateRestaurantResponse::create($restaurant);
    }
}
