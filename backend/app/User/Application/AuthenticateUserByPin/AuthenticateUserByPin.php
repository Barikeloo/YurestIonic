<?php

namespace App\User\Application\AuthenticateUserByPin;

use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\User\Application\AuthenticateUser\AuthenticateUserResponse;
use App\User\Domain\Interfaces\PasswordHasherInterface;
use App\User\Domain\Interfaces\UserRepositoryInterface;
use App\User\Infrastructure\Persistence\Models\EloquentUser;

final class AuthenticateUserByPin
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly PasswordHasherInterface $passwordHasher,
    ) {}

    public function __invoke(string $userUuid, string $pin): AuthenticateUserResponse
    {
        $persistedUser = EloquentUser::query()
            ->select('uuid', 'pin', 'role', 'restaurant_id')
            ->where('uuid', $userUuid)
            ->first();

        if ($persistedUser === null || ! is_string($persistedUser->pin) || $persistedUser->pin === '') {
            return AuthenticateUserResponse::invalidCredentials();
        }

        $isValidHashedPin = $this->passwordHasher->verify($pin, $persistedUser->pin);
        $isValidLegacyPin = hash_equals($persistedUser->pin, $pin);

        if (! $isValidHashedPin && ! $isValidLegacyPin) {
            return AuthenticateUserResponse::invalidCredentials();
        }

        if ($isValidLegacyPin) {
            EloquentUser::query()
                ->where('uuid', $userUuid)
                ->update([
                    'pin' => $this->passwordHasher->hash($pin),
                    'updated_at' => now(),
                ]);
        }

        $user = $this->userRepository->findById($userUuid);

        if ($user === null) {
            return AuthenticateUserResponse::notFound();
        }

        $role = $persistedUser->role;
        $restaurantId = null;
        $restaurantName = null;

        if (is_numeric($persistedUser->restaurant_id)) {
            $restaurant = EloquentRestaurant::query()
                ->select('uuid', 'name')
                ->find((int) $persistedUser->restaurant_id);

            if ($restaurant !== null) {
                $restaurantId = $restaurant->uuid;
                $restaurantName = $restaurant->name;
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
