<?php

namespace App\User\Application\DeleteRestaurantUser;

use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Audit\Domain\ValueObject\ActionSlug;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Interfaces\UserRepositoryInterface;

class DeleteRestaurantUser
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private readonly AuditRecorderInterface $auditRecorder,
    ) {}

    public function __invoke(DeleteRestaurantUserCommand $command): DeleteRestaurantUserResponse
    {
        $user = $this->userRepository->findById($command->userUuid);

        if ($user === null) {
            throw UserNotFoundException::withId($command->userUuid);
        }

        $userRestaurantId = $user->restaurantId();
        if ($userRestaurantId === null || $userRestaurantId->value() !== $command->restaurantUuid) {
            throw UserNotFoundException::withId($command->userUuid);
        }

        $deletedUserName = $user->name()->value();
        $deletedUserEmail = $user->email()->value();
        $deletedUserRole = $user->role()?->value();

        $this->userRepository->delete($command->userUuid);

        $this->auditRecorder->record(new AuditEventDraft(
            restaurantId: Uuid::create($command->restaurantUuid),
            slug: ActionSlug::create('user.deleted'),
            entityType: 'user',
            entityId: $command->userUuid,
            userId: $command->actorUserUuid !== null ? Uuid::create($command->actorUserUuid) : null,
            deviceId: $command->deviceId,
            ipAddress: $command->ipAddress,
            metadata: [
                'user_name' => $deletedUserName,
                'email' => $deletedUserEmail,
                'role' => $deletedUserRole,
                'actor_type' => $command->actorSuperAdminUuid !== null ? 'super_admin' : 'restaurant_admin',
                'actor_super_admin_id' => $command->actorSuperAdminUuid,
            ],
        ));

        return DeleteRestaurantUserResponse::create($command->userUuid);
    }
}
