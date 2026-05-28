<?php

namespace App\User\Application\AuthenticateUserByPin;

use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Audit\Domain\ValueObject\ActionSlug;
use App\Restaurant\Domain\Interfaces\RestaurantRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\Exception\InvalidCredentialsException;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Interfaces\PasswordHasherInterface;
use App\User\Domain\Interfaces\UserQuickAccessRepositoryInterface;
use App\User\Domain\Interfaces\UserRepositoryInterface;

final class AuthenticateUserByPin
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly RestaurantRepositoryInterface $restaurantRepository,
        private readonly PasswordHasherInterface $passwordHasher,
        private readonly UserQuickAccessRepositoryInterface $userQuickAccessRepository,
        private readonly AuditRecorderInterface $auditRecorder,
    ) {}

    public function __invoke(AuthenticateUserByPinCommand $command): AuthenticateUserByPinResponse
    {
        $persistedPin = $this->userRepository->findPinByUuid($command->userUuid, $command->restaurantUuid);

        if ($persistedPin === null) {
            $this->recordLoginPinFailed($command);
            throw new InvalidCredentialsException;
        }

        $isValidHashedPin = $this->passwordHasher->verify($command->pin, $persistedPin);
        $isValidLegacyPin = hash_equals($persistedPin, $command->pin);

        if (! $isValidHashedPin && ! $isValidLegacyPin) {
            $this->recordLoginPinFailed($command);
            throw new InvalidCredentialsException;
        }

        if ($isValidLegacyPin) {
            $this->userRepository->updatePinHash($command->userUuid, $this->passwordHasher->hash($command->pin));
        }

        $user = $this->userRepository->findById($command->userUuid)
            ?? throw UserNotFoundException::withId($command->userUuid);

        $role = $user->role()?->value();
        $restaurantUuid = null;
        $restaurantName = null;

        if ($user->restaurantId() !== null) {
            $restaurant = $this->restaurantRepository->findByInternalId($user->restaurantId()->toInt());

            if ($restaurant !== null) {
                $restaurantUuid = $restaurant->uuid()->value();
                $restaurantName = $restaurant->name()->value();
            }
        }

        if ($command->deviceId !== null && $command->deviceId !== '') {
            $this->userQuickAccessRepository->recordAccess($user->id()->value(), $command->deviceId);
        }

        $this->recordLoginPinOk($command, $restaurantUuid);

        return AuthenticateUserByPinResponse::create(
            id: $user->id()->value(),
            name: $user->name()->value(),
            email: $user->email()->value(),
            role: $role,
            restaurantId: $restaurantUuid,
            restaurantName: $restaurantName,
        );
    }

    private function recordLoginPinOk(AuthenticateUserByPinCommand $command, ?string $restaurantUuid): void
    {
        if ($restaurantUuid === null) {
            return;
        }

        $this->auditRecorder->record(new AuditEventDraft(
            restaurantId: Uuid::create($restaurantUuid),
            slug: ActionSlug::create('auth.login_pin_ok'),
            entityType: 'auth_attempt',
            entityId: $command->userUuid,
            userId: Uuid::create($command->userUuid),
            ipAddress: $command->ipAddress,
            deviceId: $command->deviceId,
        ));
    }

    private function recordLoginPinFailed(AuthenticateUserByPinCommand $command): void
    {
        $user = $this->userRepository->findById($command->userUuid);
        $restaurantUuid = null;

        if ($user?->restaurantId() !== null) {
            $restaurant = $this->restaurantRepository->findByInternalId($user->restaurantId()->toInt());
            $restaurantUuid = $restaurant?->uuid()->value();
        }

        if ($restaurantUuid === null) {
            $restaurantUuid = $command->restaurantUuid;
        }

        if ($restaurantUuid === null) {
            return;
        }

        $this->auditRecorder->record(new AuditEventDraft(
            restaurantId: Uuid::create($restaurantUuid),
            slug: ActionSlug::create('auth.login_pin_failed'),
            entityType: 'auth_attempt',
            entityId: $command->userUuid,
            userId: Uuid::create($command->userUuid),
            ipAddress: $command->ipAddress,
            deviceId: $command->deviceId,
        ));
    }
}
