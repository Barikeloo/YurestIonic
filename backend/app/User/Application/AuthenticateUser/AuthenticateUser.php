<?php

namespace App\User\Application\AuthenticateUser;

use App\Restaurant\Domain\Interfaces\RestaurantRepositoryInterface;
use App\Shared\Domain\ValueObject\Email;
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
    ) {}

    public function __invoke(AuthenticateUserCommand $command): AuthenticateUserResponse
    {
        $user = $this->userRepository->findByEmail(Email::create($command->email))
            ?? throw UserNotFoundException::withEmail($command->email);

        if (! $user->verifyPassword($command->plainPassword, $this->passwordHasher)) {
            throw new InvalidCredentialsException();
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

        return new AuthenticateUserResponse(
            id: $user->id()->value(),
            name: $user->name()->value(),
            email: $user->email()->value(),
            role: $role,
            restaurantId: $restaurantId,
            restaurantName: $restaurantName,
        );
    }
}
