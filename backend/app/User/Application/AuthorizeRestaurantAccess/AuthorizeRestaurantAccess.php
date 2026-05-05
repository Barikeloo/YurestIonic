<?php

namespace App\User\Application\AuthorizeRestaurantAccess;

use App\Restaurant\Domain\Interfaces\RestaurantRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\Exception\ForbiddenRestaurantAccessException;
use App\User\Domain\Exception\NotAuthenticatedException;
use App\User\Domain\Exception\RestaurantNotFoundException;
use App\User\Domain\Interfaces\UserRepositoryInterface;

class AuthorizeRestaurantAccess
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private RestaurantRepositoryInterface $restaurantRepository,
    ) {}

    public function __invoke(AuthorizeRestaurantAccessCommand $command): AuthorizeRestaurantAccessResponse
    {
        if ($command->authUserUuid === '') {
            throw new NotAuthenticatedException();
        }

        $user = $this->userRepository->findById($command->authUserUuid);

        if ($user === null || $user->restaurantId() === null) {
            throw new NotAuthenticatedException();
        }

        $linkedRestaurant = $this->restaurantRepository->findByInternalId($user->restaurantId()->toInt());
        $targetRestaurant = $this->restaurantRepository->findByUuid(Uuid::create($command->targetRestaurantUuid));

        if ($linkedRestaurant === null || $targetRestaurant === null) {
            throw new RestaurantNotFoundException();
        }

        $linkedTaxId = $linkedRestaurant->taxId()?->value();
        $targetTaxId = $targetRestaurant->taxId()?->value();

        if (! is_string($linkedTaxId) || $linkedTaxId === '') {
            if ($targetRestaurant->uuid()->value() !== $linkedRestaurant->uuid()->value()) {
                throw new ForbiddenRestaurantAccessException();
            }

            return new AuthorizeRestaurantAccessResponse();
        }

        if ($linkedTaxId !== $targetTaxId) {
            throw new ForbiddenRestaurantAccessException();
        }

        return new AuthorizeRestaurantAccessResponse();
    }
}
