<?php

namespace App\User\Application\UpdateRestaurantUser;

use App\Shared\Application\Event\EventBusInterface;
use App\User\Domain\Event\UserPasswordChanged;
use App\User\Domain\Event\UserUpdated;
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
        private readonly EventBusInterface $eventBus,
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
            throw new CannotDemoteSelfAdminException;
        }

        $before = [
            'name' => $user->name()->value(),
            'email' => $user->email()->value(),
            'role' => $user->role()?->value(),
        ];

        $updates = [];
        $passwordChanged = false;
        $pinChanged = false;

        if ($command->name !== null) {
            $updates['name'] = $command->name;
        }

        if ($command->email !== null) {
            $updates['email'] = $command->email;
        }

        if ($command->plainPassword !== null) {
            $updates['password'] = $this->passwordHasher->hash($command->plainPassword);
            $passwordChanged = true;
        }

        if ($command->role !== null) {
            $updates['role'] = $command->role;
        }

        if ($command->plainPin !== null) {
            $pinHash = $this->passwordHasher->hash($command->plainPin);
            if ($this->userRepository->pinHashExistsForRestaurant($pinHash, $command->restaurantUuid, $command->userUuid)) {
                throw new PinAlreadyInUseException;
            }
            $updates['pin'] = $pinHash;
            $pinChanged = true;
        }

        if (empty($updates)) {
            return UpdateRestaurantUserResponse::create($command->userUuid);
        }

        $this->userRepository->updatePartial($command->userUuid, $updates);

        $after = [
            'name' => $updates['name'] ?? $before['name'],
            'email' => $updates['email'] ?? $before['email'],
            'role' => $updates['role'] ?? $before['role'],
        ];

        $changedFields = [];
        if ($before['name'] !== $after['name']) {
            $changedFields[] = 'nombre';
        }
        if ($before['email'] !== $after['email']) {
            $changedFields[] = 'email';
        }
        if ($before['role'] !== $after['role']) {
            $changedFields[] = 'rol';
        }
        if ($passwordChanged) {
            $changedFields[] = 'contraseña';
        }
        if ($pinChanged) {
            $changedFields[] = 'PIN';
        }

        if (count($changedFields) > 0) {
            $events = [new UserUpdated(
                userUuid: $command->userUuid,
                before: $before,
                after: $after,
                metadata: [
                    'user_name' => $after['name'],
                    'changed_fields' => implode(', ', $changedFields),
                    'password_changed' => $passwordChanged,
                    'pin_changed' => $pinChanged,
                    'actor_type' => $command->actorSuperAdminUuid !== null ? 'super_admin' : 'restaurant_admin',
                    'actor_super_admin_id' => $command->actorSuperAdminUuid,
                ],
                restaurantUuid: $command->restaurantUuid,
            )];

            if ($passwordChanged) {
                $events[] = new UserPasswordChanged(
                    userUuid: $command->userUuid,
                    restaurantUuid: $command->restaurantUuid,
                );
            }

            $this->eventBus->publish(...$events);
        }

        return UpdateRestaurantUserResponse::create($command->userUuid);
    }
}
