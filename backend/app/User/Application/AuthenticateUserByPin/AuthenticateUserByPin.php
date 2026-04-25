<?php

namespace App\User\Application\AuthenticateUserByPin;

use App\Restaurant\Domain\Interfaces\RestaurantRepositoryInterface;
use App\User\Application\AuthenticateUser\AuthenticateUserResponse;
use App\User\Domain\Interfaces\PasswordHasherInterface;
use App\User\Domain\Interfaces\UserRepositoryInterface;

final class AuthenticateUserByPin
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly RestaurantRepositoryInterface $restaurantRepository,
        private readonly PasswordHasherInterface $passwordHasher,
    ) {}

    public function __invoke(string $userUuid, string $pin, ?string $restaurantUuid = null): AuthenticateUserResponse
    {
        $persistedPin = $this->userRepository->findPinByUuid($userUuid, $restaurantUuid);

        if ($persistedPin === null) {
            return AuthenticateUserResponse::invalidCredentials();
        }

        $isValidHashedPin = $this->passwordHasher->verify($pin, $persistedPin);
        $isValidLegacyPin = hash_equals($persistedPin, $pin);

        if (! $isValidHashedPin && ! $isValidLegacyPin) {
            return AuthenticateUserResponse::invalidCredentials();
        }

        if ($isValidLegacyPin) {
            $this->userRepository->updatePinHash($userUuid, $this->passwordHasher->hash($pin));
        }

        $user = $this->userRepository->findById($userUuid);

        if ($user === null) {
            return AuthenticateUserResponse::notFound();
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

        return AuthenticateUserResponse::authenticated(
            $user,
            $role,
            $restaurantId,
            $restaurantName,
        );
    }
}
