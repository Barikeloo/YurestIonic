<?php

namespace App\User\Application\UpdateRestaurantUser;

use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Audit\Domain\ValueObject\ActionSlug;
use App\Shared\Domain\ValueObject\Uuid;
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
        private readonly AuditRecorderInterface $auditRecorder,
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
            $this->auditRecorder->record(new AuditEventDraft(
                restaurantId: Uuid::create($command->restaurantUuid),
                slug: ActionSlug::create('user.updated'),
                entityType: 'user',
                entityId: $command->userUuid,
                userId: $command->actorUserUuid !== null ? Uuid::create($command->actorUserUuid) : null,
                deviceId: $command->deviceId,
                ipAddress: $command->ipAddress,
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
            ));
        }

        return UpdateRestaurantUserResponse::create($command->userUuid);
    }
}
