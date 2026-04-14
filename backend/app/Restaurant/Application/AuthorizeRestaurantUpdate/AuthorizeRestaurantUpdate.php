<?php

namespace App\Restaurant\Application\AuthorizeRestaurantUpdate;

use App\Restaurant\Domain\Interfaces\RestaurantRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\Interfaces\UserRepositoryInterface;

final class AuthorizeRestaurantUpdate
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private RestaurantRepositoryInterface $restaurantRepository,
    ) {}

    public function __invoke(?string $authUserUuid, string $targetRestaurantUuid): AuthorizeRestaurantUpdateResponse
    {
        if (! is_string($authUserUuid) || $authUserUuid === '') {
            return AuthorizeRestaurantUpdateResponse::notAuthenticated();
        }

        $user = $this->userRepository->findById($authUserUuid);

        if ($user === null) {
            return AuthorizeRestaurantUpdateResponse::forbidden();
        }

        if ($user->role() !== 'admin' || ! is_numeric($user->restaurantId())) {
            return AuthorizeRestaurantUpdateResponse::forbidden();
        }

        $linkedRestaurant = $this->restaurantRepository->findByInternalId((int) $user->restaurantId());
        $targetRestaurant = $this->restaurantRepository->findByUuid(Uuid::create($targetRestaurantUuid));

        if ($linkedRestaurant === null || $targetRestaurant === null) {
            return AuthorizeRestaurantUpdateResponse::restaurantNotFound();
        }

        $linkedTaxId = $linkedRestaurant->getTaxId()?->value();

        if (! is_string($linkedTaxId) || $linkedTaxId === '') {
            if ($targetRestaurant->getUuid()->value() !== $linkedRestaurant->getUuid()->value()) {
                return AuthorizeRestaurantUpdateResponse::forbidden();
            }

            return AuthorizeRestaurantUpdateResponse::success();
        }

        if ($targetRestaurant->getTaxId()?->value() !== $linkedTaxId) {
            return AuthorizeRestaurantUpdateResponse::forbidden();
        }

        return AuthorizeRestaurantUpdateResponse::success();
    }
}
