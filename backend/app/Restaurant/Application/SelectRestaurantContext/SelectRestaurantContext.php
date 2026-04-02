<?php

namespace App\Restaurant\Application\SelectRestaurantContext;

use App\Restaurant\Domain\Interfaces\RestaurantRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\Interfaces\UserRepositoryInterface;

class SelectRestaurantContext
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private RestaurantRepositoryInterface $restaurantRepository,
    ) {}

    public function __invoke(
        ?string $authUserUuid,
        string $targetRestaurantUuid,
        bool $isSuperAdmin,
    ): SelectRestaurantContextResponse {
        $targetRestaurant = $this->restaurantRepository->findByUuid(Uuid::create($targetRestaurantUuid));

        if ($targetRestaurant === null) {
            return SelectRestaurantContextResponse::restaurantNotFound();
        }

        if ($isSuperAdmin) {
            return SelectRestaurantContextResponse::success(
                $targetRestaurant->getUuid()->value(),
                $targetRestaurant->getName(),
            );
        }

        if (! is_string($authUserUuid) || $authUserUuid === '') {
            return SelectRestaurantContextResponse::notAuthenticated();
        }

        $user = $this->userRepository->findById($authUserUuid);

        if ($user === null || ! is_numeric($user->restaurantId())) {
            return SelectRestaurantContextResponse::notAuthenticated();
        }

        $linkedRestaurant = $this->restaurantRepository->findByInternalId((int) $user->restaurantId());

        if ($linkedRestaurant === null) {
            return SelectRestaurantContextResponse::linkedRestaurantNotFound();
        }

        $linkedTaxId = $linkedRestaurant->getTaxId();

        if (! is_string($linkedTaxId) || $linkedTaxId === '') {
            return SelectRestaurantContextResponse::linkedRestaurantWithoutTaxId();
        }

        if ($targetRestaurant->getTaxId() !== $linkedTaxId) {
            return SelectRestaurantContextResponse::forbidden();
        }

        return SelectRestaurantContextResponse::success(
            $targetRestaurant->getUuid()->value(),
            $targetRestaurant->getName(),
        );
    }
}