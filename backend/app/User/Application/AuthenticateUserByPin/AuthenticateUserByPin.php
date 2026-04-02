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

    public function __invoke(string $userUuid, string $pin): AuthenticateUserResponse
    {
        $persistedPin = $this->userRepository->findPinByUuid($userUuid);

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

        $role = $user->role();
        $restaurantId = null;
        $restaurantName = null;

        if (is_numeric($user->restaurantId())) {
            $restaurant = $this->restaurantRepository->findByInternalId((int) $user->restaurantId());

            if ($restaurant !== null) {
                $restaurantId = $restaurant->getUuid()->value();
                $restaurantName = $restaurant->getName();
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
