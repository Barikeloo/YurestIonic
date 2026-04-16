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
                $targetRestaurant->uuid()->value(),
                $targetRestaurant->name()->value(),
            );
        }

        if (! is_string($authUserUuid) || $authUserUuid === '') {
            return SelectRestaurantContextResponse::notAuthenticated();
        }

        $user = $this->userRepository->findById($authUserUuid);

        if ($user === null || $user->restaurantId() === null) {
            return SelectRestaurantContextResponse::notAuthenticated();
        }

        $linkedRestaurant = $this->restaurantRepository->findByInternalId($user->restaurantId()->toInt());

        if ($linkedRestaurant === null) {
            return SelectRestaurantContextResponse::linkedRestaurantNotFound();
        }

        $linkedTaxId = $linkedRestaurant->taxId()?->value();

        if (! is_string($linkedTaxId) || $linkedTaxId === '') {
            if ($targetRestaurant->uuid()->value() !== $linkedRestaurant->uuid()->value()) {
                return SelectRestaurantContextResponse::forbidden();
            }

            return SelectRestaurantContextResponse::success(
                $targetRestaurant->uuid()->value(),
                $targetRestaurant->name()->value(),
            );
        }

        if ($targetRestaurant->taxId()?->value() !== $linkedTaxId) {
            return SelectRestaurantContextResponse::forbidden();
        }

        return SelectRestaurantContextResponse::success(
            $targetRestaurant->uuid()->value(),
            $targetRestaurant->name()->value(),
        );
    }
}