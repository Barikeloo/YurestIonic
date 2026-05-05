<?php

namespace App\User\Application\AuthenticateUserByPin;

use App\Restaurant\Domain\Interfaces\RestaurantRepositoryInterface;
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
    ) {}

    public function __invoke(AuthenticateUserByPinCommand $command): AuthenticateUserByPinResponse
    {
        $persistedPin = $this->userRepository->findPinByUuid($command->userUuid, $command->restaurantUuid);

        if ($persistedPin === null) {
            throw new InvalidCredentialsException();
        }

        $isValidHashedPin = $this->passwordHasher->verify($command->pin, $persistedPin);
        $isValidLegacyPin = hash_equals($persistedPin, $command->pin);

        if (! $isValidHashedPin && ! $isValidLegacyPin) {
            throw new InvalidCredentialsException();
        }

        if ($isValidLegacyPin) {
            $this->userRepository->updatePinHash($command->userUuid, $this->passwordHasher->hash($command->pin));
        }

        $user = $this->userRepository->findById($command->userUuid)
            ?? throw UserNotFoundException::withId($command->userUuid);

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

        return new AuthenticateUserByPinResponse(
            id: $user->id()->value(),
            name: $user->name()->value(),
            email: $user->email()->value(),
            role: $role,
            restaurantId: $restaurantId,
            restaurantName: $restaurantName,
        );
    }
}
