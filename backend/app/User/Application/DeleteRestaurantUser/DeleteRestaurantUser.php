<?php

namespace App\User\Application\DeleteRestaurantUser;

use App\Shared\Application\Event\EventBusInterface;
use App\User\Domain\Event\UserDeleted;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Interfaces\UserRepositoryInterface;

class DeleteRestaurantUser
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private readonly EventBusInterface $eventBus,
    ) {}

    public function __invoke(DeleteRestaurantUserCommand $command): DeleteRestaurantUserResponse
    {
        $user = $this->userRepository->findById($command->userUuid);

        if ($user === null) {
            throw UserNotFoundException::withId($command->userUuid);
        }

        if (! $this->userRepository->userBelongsToRestaurant($command->userUuid, $command->restaurantUuid)) {
            throw UserNotFoundException::withId($command->userUuid);
        }

        $deletedUserName = $user->name()->value();
        $deletedUserEmail = $user->email()->value();
        $deletedUserRole = $user->role()?->value();

        $this->userRepository->delete($command->userUuid);

        $this->eventBus->publish(new UserDeleted(
            userUuid: $command->userUuid,
            name: $deletedUserName,
            email: $deletedUserEmail,
            role: $deletedUserRole,
            actorSuperAdminUuid: $command->actorSuperAdminUuid,
            restaurantUuid: $command->restaurantUuid,
        ));

        return DeleteRestaurantUserResponse::create($command->userUuid);
    }
}
