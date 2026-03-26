<?php

namespace App\User\Application\AuthenticateUser;

use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\Shared\Domain\ValueObject\Email;
use App\User\Domain\Interfaces\PasswordHasherInterface;
use App\User\Domain\Interfaces\UserRepositoryInterface;
use App\User\Infrastructure\Persistence\Models\EloquentUser;

class AuthenticateUser
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PasswordHasherInterface $passwordHasher,
    ) {}

    public function __invoke(string $email, string $plainPassword): AuthenticateUserResponse
    {
        $emailVO = Email::create($email);
        $user = $this->userRepository->findByEmail($emailVO->value());

        if ($user === null) {
            return AuthenticateUserResponse::notFound();
        }

        $isValidPassword = $this->passwordHasher->verify($plainPassword, $user->passwordHash());

        if (! $isValidPassword) {
            return AuthenticateUserResponse::invalidCredentials();
        }

        // Get additional data from EloquentUser
        $persistedUser = EloquentUser::query()
            ->select('role', 'restaurant_id')
            ->where('uuid', $user->id()->value())
            ->first();

        $role = $persistedUser?->role;
        $restaurantId = null;
        $restaurantName = null;

        if ($persistedUser !== null && is_numeric($persistedUser->restaurant_id)) {
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

