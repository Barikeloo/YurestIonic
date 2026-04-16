<?php

namespace App\Restaurant\Application\GetAdminRestaurantCollection;

use App\Restaurant\Domain\Entity\Restaurant;
use App\Restaurant\Domain\Interfaces\RestaurantRepositoryInterface;
use App\User\Domain\Interfaces\UserRepositoryInterface;

class GetAdminRestaurantCollection
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private RestaurantRepositoryInterface $restaurantRepository,
    ) {}

    public function __invoke(?string $authUserUuid, bool $isSuperAdmin): GetAdminRestaurantCollectionResponse
    {
        if ($isSuperAdmin) {
            return GetAdminRestaurantCollectionResponse::success(
                $this->mapRestaurants($this->restaurantRepository->all()),
            );
        }

        if (! is_string($authUserUuid) || $authUserUuid === '') {
            return GetAdminRestaurantCollectionResponse::notAuthenticated();
        }

        $user = $this->userRepository->findById($authUserUuid);

        if ($user === null || $user->restaurantId() === null) {
            return GetAdminRestaurantCollectionResponse::notAuthenticated();
        }

        $linkedRestaurant = $this->restaurantRepository->findByInternalId($user->restaurantId()->toInt());

        if ($linkedRestaurant === null) {
            return GetAdminRestaurantCollectionResponse::linkedRestaurantNotFound();
        }

        $taxId = $linkedRestaurant->taxId()?->value();

        if (! is_string($taxId) || $taxId === '') {
            return GetAdminRestaurantCollectionResponse::success(
                $this->mapRestaurants([$linkedRestaurant]),
            );
        }

        return GetAdminRestaurantCollectionResponse::success(
            $this->mapRestaurants($this->restaurantRepository->findByTaxId($taxId)),
        );
    }

    /**
     * @param array<Restaurant> $restaurants
        * @return array<array{uuid: string, name: string, legal_name: string|null, tax_id: string|null, email: string, users: int, zones: int, products: int}>
     */
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