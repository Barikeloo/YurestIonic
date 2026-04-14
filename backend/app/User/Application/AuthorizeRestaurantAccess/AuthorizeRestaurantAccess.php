<?php

namespace App\User\Application\AuthorizeRestaurantAccess;

use App\Restaurant\Domain\Interfaces\RestaurantRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\Interfaces\UserRepositoryInterface;

class AuthorizeRestaurantAccess
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private RestaurantRepositoryInterface $restaurantRepository,
    ) {}

    public function __invoke(string $authUserUuid, string $targetRestaurantUuid): AuthorizeRestaurantAccessResponse
    {
        if ($authUserUuid === '') {
            return AuthorizeRestaurantAccessResponse::notAuthenticated();
        }

        $user = $this->userRepository->findById($authUserUuid);

        if ($user === null || ! is_numeric($user->restaurantId())) {
            return AuthorizeRestaurantAccessResponse::notAuthenticated();
        }

        $linkedRestaurant = $this->restaurantRepository->findByInternalId((int) $user->restaurantId());
        $targetRestaurant = $this->restaurantRepository->findByUuid(Uuid::create($targetRestaurantUuid));

        if ($linkedRestaurant === null || $targetRestaurant === null) {
            return AuthorizeRestaurantAccessResponse::restaurantNotFound();
        }

        $linkedTaxId = $linkedRestaurant->getTaxId()?->value();
        $targetTaxId = $targetRestaurant->getTaxId()?->value();

        if (! is_string($linkedTaxId) || $linkedTaxId === '') {
            if ($targetRestaurant->getUuid()->value() !== $linkedRestaurant->getUuid()->value()) {
                return AuthorizeRestaurantAccessResponse::forbidden();
            }

            return AuthorizeRestaurantAccessResponse::authorized();
        }

        if ($linkedTaxId !== $targetTaxId) {
            return AuthorizeRestaurantAccessResponse::forbidden();
        }

        return AuthorizeRestaurantAccessResponse::authorized();
    }
}