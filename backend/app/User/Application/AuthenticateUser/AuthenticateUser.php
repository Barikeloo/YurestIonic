<?php

namespace App\User\Application\AuthenticateUser;

use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Audit\Domain\ValueObject\ActionSlug;
use App\Restaurant\Domain\Interfaces\RestaurantRepositoryInterface;
use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\Exception\InvalidCredentialsException;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Interfaces\PasswordHasherInterface;
use App\User\Domain\Interfaces\UserQuickAccessRepositoryInterface;
use App\User\Domain\Interfaces\UserRepositoryInterface;

class AuthenticateUser
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private RestaurantRepositoryInterface $restaurantRepository,
        private PasswordHasherInterface $passwordHasher,
        private UserQuickAccessRepositoryInterface $userQuickAccessRepository,
        private readonly AuditRecorderInterface $auditRecorder,
    ) {}

    public function __invoke(AuthenticateUserCommand $command): AuthenticateUserResponse
    {
        $user = $this->userRepository->findByEmail(Email::create($command->email));

        if ($user === null) {
            $this->auditRecorder->record(new AuditEventDraft(
                restaurantId: null,
                slug: ActionSlug::create('auth.login_failed'),
                entityType: 'auth_attempt',
                entityId: $command->email,
                userId: null,
                deviceId: $command->deviceId,
                ipAddress: $command->ipAddress,
                metadata: ['email' => $command->email],
            ));
            throw UserNotFoundException::withEmail($command->email);
        }

        if (! $user->verifyPassword($command->plainPassword, $this->passwordHasher)) {
            $this->auditRecorder->record(new AuditEventDraft(
                restaurantId: null,
                slug: ActionSlug::create('auth.login_failed'),
                entityType: 'auth_attempt',
                entityId: $user->id()->value(),
                userId: $user->id(),
                deviceId: $command->deviceId,
                ipAddress: $command->ipAddress,
                metadata: ['email' => $command->email],
            ));
            throw new InvalidCredentialsException;
        }

        $role = $user->role()?->value();
        $restaurantId = null;
        $restaurantName = null;

        if ($user->restaurantId() !== null) {
            $restaurant = $this->restaurantRepository->findByInternalId($user->restaurantId()->toInt());

            if ($restaurant !== null) {
                $restaurantId = $restaurant->uuid()->value();
                $restaurantName = $restaurant->name()->value();
            }
        }

        if ($command->deviceId !== null && $command->deviceId !== '') {
            $this->userQuickAccessRepository->recordAccess($user->id()->value(), $command->deviceId);
        }

        if ($restaurantId !== null) {
            $this->auditRecorder->record(new AuditEventDraft(
                restaurantId: Uuid::create($restaurantId),
                slug: ActionSlug::create('auth.login_successful'),
                entityType: 'auth_attempt',
                entityId: $user->id()->value(),
                userId: $user->id(),
                deviceId: $command->deviceId,
                ipAddress: $command->ipAddress,
            ));
        }

        return AuthenticateUserResponse::create(
            id: $user->id()->value(),
            name: $user->name()->value(),
            email: $user->email()->value(),
            role: $role,
            restaurantId: $restaurantId,
            restaurantName: $restaurantName,
        );
    }
}
