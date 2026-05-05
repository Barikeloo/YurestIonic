<?php

namespace App\User\Application\GetMe;

use App\Restaurant\Domain\Interfaces\RestaurantRepositoryInterface;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Interfaces\UserRepositoryInterface;

class GetMe
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private RestaurantRepositoryInterface $restaurantRepository,
    ) {}

    public function __invoke(GetMeCommand $command): GetMeResponse
    {
        $user = $this->userRepository->findById($command->userId)
            ?? throw UserNotFoundException::withId($command->userId);

        $restaurantInternalId = $user->restaurantId()?->toInt();
        $restaurantUuid = null;
        $restaurantName = null;

        if ($restaurantInternalId !== null) {
            $restaurant = $this->restaurantRepository->findByInternalId($restaurantInternalId);
            if ($restaurant !== null) {
                $restaurantUuid = $restaurant->uuid()->value();
                $restaurantName = $restaurant->name()->value();
            }
        }

        return new GetMeResponse(
            id: $user->id()->value(),
            name: $user->name()->value(),
            email: $user->email()->value(),
            role: $user->role()?->value(),
            restaurantId: $restaurantUuid,
            restaurantName: $restaurantName,
        );
    }
}
