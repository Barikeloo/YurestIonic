<?php

namespace App\User\Application\AuthenticateForDeviceLink;

use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Audit\Domain\ValueObject\ActionSlug;
use App\Restaurant\Domain\Interfaces\RestaurantRepositoryInterface;
use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\Exception\InvalidCredentialsException;
use App\User\Domain\Exception\OnlyAdminsCanLinkDeviceException;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Interfaces\PasswordHasherInterface;
use App\User\Domain\Interfaces\UserQuickAccessRepositoryInterface;
use App\User\Domain\Interfaces\UserRepositoryInterface;

class AuthenticateForDeviceLink
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private RestaurantRepositoryInterface $restaurantRepository,
        private PasswordHasherInterface $passwordHasher,
        private UserQuickAccessRepositoryInterface $userQuickAccessRepository,
        private AuditRecorderInterface $auditRecorder,
    ) {}

    public function __invoke(AuthenticateForDeviceLinkCommand $command): AuthenticateForDeviceLinkResponse
    {
        $user = $this->userRepository->findByEmail(Email::create($command->email))
            ?? throw UserNotFoundException::withEmail($command->email);

        if (! $user->verifyPassword($command->password, $this->passwordHasher)) {
            throw new InvalidCredentialsException;
        }

        $role = $user->role();
        if ($role === null || ! $role->isAdmin()) {
            throw new OnlyAdminsCanLinkDeviceException;
        }

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
                slug: ActionSlug::create('auth.device_link'),
                entityType: 'user_session',
                entityId: $user->id()->value(),
                userId: Uuid::create($user->id()->value()),
                ipAddress: $command->ipAddress,
                deviceId: $command->deviceId,
            ));
        }

        return AuthenticateForDeviceLinkResponse::create(
            id: $user->id()->value(),
            name: $user->name()->value(),
            email: $user->email()->value(),
            restaurantId: $restaurantId,
            restaurantName: $restaurantName,
        );
    }
}
