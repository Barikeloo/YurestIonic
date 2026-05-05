<?php

namespace App\User\Application\UpdateRestaurantUser;

use App\User\Domain\Exception\CannotDemoteSelfAdminException;
use App\User\Domain\Exception\PinAlreadyInUseException;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Interfaces\PasswordHasherInterface;
use App\User\Domain\Interfaces\UserRepositoryInterface;
use App\User\Domain\ValueObject\Role;

class UpdateRestaurantUser
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PasswordHasherInterface $passwordHasher,
    ) {}

    public function __invoke(UpdateRestaurantUserCommand $command): UpdateRestaurantUserResponse
    {
        $user = $this->userRepository->findById($command->userUuid);

        if ($user === null) {
            throw UserNotFoundException::withId($command->userUuid);
        }

        if (! $this->userRepository->userBelongsToRestaurant($command->userUuid, $command->restaurantUuid)) {
            throw UserNotFoundException::withId($command->userUuid);
        }

        if (
            is_string($command->actorUserUuid)
            && $command->actorUserUuid !== ''
            && $command->actorUserUuid === $command->userUuid
            && is_string($command->role)
            && $command->role !== ''
            && ! Role::create($command->role)->isAdmin()
        ) {
            throw new CannotDemoteSelfAdminException();
        }

        $updates = [];

        if ($command->name !== null) {
            $updates['name'] = $command->name;
        }

        if ($command->email !== null) {
            $updates['email'] = $command->email;
        }

        if ($command->plainPassword !== null) {
            $updates['password'] = $this->passwordHasher->hash($command->plainPassword);
        }

        if ($command->role !== null) {
            $updates['role'] = $command->role;
        }

        if ($command->plainPin !== null) {
            $pinHash = $this->passwordHasher->hash($command->plainPin);
            if ($this->userRepository->pinHashExistsForRestaurant($pinHash, $command->restaurantUuid, $command->userUuid)) {
                throw new PinAlreadyInUseException();
            }
            $updates['pin'] = $pinHash;
        }

        if (empty($updates)) {
            return new UpdateRestaurantUserResponse($command->userUuid);
        }

        $this->userRepository->updatePartial($command->userUuid, $updates);

        return new UpdateRestaurantUserResponse($command->userUuid);
    }
}
