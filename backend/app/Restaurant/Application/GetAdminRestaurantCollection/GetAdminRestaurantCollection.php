<?php

namespace App\Restaurant\Application\GetAdminRestaurantCollection;

use App\Restaurant\Domain\Entity\Restaurant;
use App\Restaurant\Domain\Exception\LinkedRestaurantNotFoundException;
use App\Restaurant\Domain\Exception\NotAuthenticatedException;
use App\Restaurant\Domain\Interfaces\RestaurantRepositoryInterface;
use App\User\Domain\Interfaces\UserRepositoryInterface;

class GetAdminRestaurantCollection
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private RestaurantRepositoryInterface $restaurantRepository,
    ) {}

    public function __invoke(GetAdminRestaurantCollectionCommand $command): GetAdminRestaurantCollectionResponse
    {
        if ($command->isSuperAdmin) {
            return GetAdminRestaurantCollectionResponse::create(
                $this->mapRestaurants($this->restaurantRepository->all()),
            );
        }

        if (! is_string($command->authUserUuid) || $command->authUserUuid === '') {
            throw NotAuthenticatedException::create();
        }

        $user = $this->userRepository->findById($command->authUserUuid);

        if ($user === null || $user->restaurantId() === null) {
            throw NotAuthenticatedException::create();
        }

        $linkedRestaurant = $this->restaurantRepository->findByInternalId($user->restaurantId()->toInt());

        if ($linkedRestaurant === null) {
            throw LinkedRestaurantNotFoundException::create();
        }

        $taxId = $linkedRestaurant->taxId()?->value();

        if (! is_string($taxId) || $taxId === '') {
            return GetAdminRestaurantCollectionResponse::create(
                $this->mapRestaurants([$linkedRestaurant]),
            );
        }

        return GetAdminRestaurantCollectionResponse::create(
            $this->mapRestaurants($this->restaurantRepository->findByTaxId($taxId)),
        );
    }

    private function mapRestaurants(array $restaurants): array
    {
        usort(
            $restaurants,
            static fn (Restaurant $left, Restaurant $right): int => strcmp($left->name()->value(), $right->name()->value()),
        );

        return array_map(
            function (Restaurant $restaurant): array {
                $kpis = $this->restaurantRepository->getKpisByUuid($restaurant->uuid());

                return [
                    'uuid' => $restaurant->uuid()->value(),
                    'name' => $restaurant->name()->value(),
                    'legal_name' => $restaurant->legalName()?->value(),
                    'tax_id' => $restaurant->taxId()?->value(),
                    'email' => $restaurant->email()->value(),
                    'users' => $kpis['users'],
                    'zones' => $kpis['zones'],
                    'products' => $kpis['products'],
                ];
            },
            $restaurants,
        );
    }
}
