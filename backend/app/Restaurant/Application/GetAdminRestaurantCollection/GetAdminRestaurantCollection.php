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

        if ($user === null || ! is_numeric($user->restaurantId())) {
            return GetAdminRestaurantCollectionResponse::notAuthenticated();
        }

        $linkedRestaurant = $this->restaurantRepository->findByInternalId((int) $user->restaurantId());

        if ($linkedRestaurant === null) {
            return GetAdminRestaurantCollectionResponse::linkedRestaurantNotFound();
        }

        $taxId = $linkedRestaurant->getTaxId()?->value();

        if (! is_string($taxId) || $taxId === '') {
            return GetAdminRestaurantCollectionResponse::linkedRestaurantWithoutTaxId();
        }

        return GetAdminRestaurantCollectionResponse::success(
            $this->mapRestaurants($this->restaurantRepository->findByTaxId($taxId)),
        );
    }

    /**
     * @param array<Restaurant> $restaurants
     * @return array<array{uuid: string, name: string, legal_name: string|null, tax_id: string|null, email: string}>
     */
    private function mapRestaurants(array $restaurants): array
    {
        usort(
            $restaurants,
            static fn (Restaurant $left, Restaurant $right): int => strcmp($left->getName()->value(), $right->getName()->value()),
        );

        return array_map(
            static fn (Restaurant $restaurant): array => [
                'uuid' => $restaurant->getUuid()->value(),
                'name' => $restaurant->getName()->value(),
                'legal_name' => $restaurant->getLegalName()?->value(),
                'tax_id' => $restaurant->getTaxId()?->value(),
                'email' => $restaurant->getEmail()->value(),
            ],
            $restaurants,
        );
    }
}