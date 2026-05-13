<?php

namespace App\Restaurant\Application\SelectRestaurantContext;

use App\Restaurant\Domain\Exception\ForbiddenException;
use App\Restaurant\Domain\Exception\LinkedRestaurantNotFoundException;
use App\Restaurant\Domain\Exception\NotAuthenticatedException;
use App\Restaurant\Domain\Exception\RestaurantNotFoundException;
use App\Restaurant\Domain\Interfaces\RestaurantRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\Interfaces\UserRepositoryInterface;

class SelectRestaurantContext
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private RestaurantRepositoryInterface $restaurantRepository,
    ) {}

    public function __invoke(SelectRestaurantContextCommand $command): SelectRestaurantContextResponse
    {
        $targetRestaurant = $this->restaurantRepository->findByUuid(Uuid::create($command->targetRestaurantUuid));

        if ($targetRestaurant === null) {
            throw RestaurantNotFoundException::withUuid($command->targetRestaurantUuid);
        }

        if ($command->isSuperAdmin) {
            return SelectRestaurantContextResponse::create(
                $targetRestaurant->uuid()->value(),
                $targetRestaurant->name()->value(),
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

        $linkedTaxId = $linkedRestaurant->taxId()?->value();

        if (! is_string($linkedTaxId) || $linkedTaxId === '') {
            if ($targetRestaurant->uuid()->value() !== $linkedRestaurant->uuid()->value()) {
                throw ForbiddenException::create();
            }

            return SelectRestaurantContextResponse::create(
                $targetRestaurant->uuid()->value(),
                $targetRestaurant->name()->value(),
            );
        }

        if ($targetRestaurant->taxId()?->value() !== $linkedTaxId) {
            throw ForbiddenException::create();
        }

        return SelectRestaurantContextResponse::create(
            $targetRestaurant->uuid()->value(),
            $targetRestaurant->name()->value(),
        );
    }
}
