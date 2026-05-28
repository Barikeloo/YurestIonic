<?php

namespace App\User\Application\CreateRestaurantUser;

use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Audit\Domain\ValueObject\ActionSlug;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\Exception\PinAlreadyInUseException;
use App\User\Domain\Interfaces\PasswordHasherInterface;
use App\User\Domain\Interfaces\UserRepositoryInterface;

class CreateRestaurantUser
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PasswordHasherInterface $passwordHasher,
        private readonly AuditRecorderInterface $auditRecorder,
    ) {}

    public function __invoke(CreateRestaurantUserCommand $command): CreateRestaurantUserResponse
    {
        $userUuid = Uuid::generate()->value();
        $passwordHash = $this->passwordHasher->hash($command->plainPassword);
        $pinHash = is_string($command->plainPin) && $command->plainPin !== ''
            ? $this->passwordHasher->hash($command->plainPin)
            : null;

        if ($pinHash !== null && $this->userRepository->pinHashExistsForRestaurant($pinHash, $command->restaurantUuid)) {
            throw new PinAlreadyInUseException;
        }

        $this->userRepository->saveWithRestaurant(
            $userUuid,
            $command->name,
            $command->email,
            $passwordHash,
            $command->restaurantUuid,
            $command->role,
            $pinHash,
        );

        $this->auditRecorder->record(new AuditEventDraft(
            restaurantId: Uuid::create($command->restaurantUuid),
            slug: ActionSlug::create('user.created'),
            entityType: 'user',
            entityId: $userUuid,
            userId: $command->actorUserUuid !== null ? Uuid::create($command->actorUserUuid) : null,
            deviceId: $command->deviceId,
            ipAddress: $command->ipAddress,
            metadata: [
                'user_name' => $command->name,
                'email' => $command->email,
                'role' => $command->role,
                'has_pin' => $pinHash !== null,
                'actor_type' => $command->actorSuperAdminUuid !== null ? 'super_admin' : 'restaurant_admin',
                'actor_super_admin_id' => $command->actorSuperAdminUuid,
            ],
        ));

        return CreateRestaurantUserResponse::create(
            uuid: $userUuid,
            name: $command->name,
            email: $command->email,
            role: $command->role,
        );
    }
}
