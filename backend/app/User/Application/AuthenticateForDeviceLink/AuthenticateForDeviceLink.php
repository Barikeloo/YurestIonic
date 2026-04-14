<?php

namespace App\User\Application\AuthenticateForDeviceLink;

use App\Restaurant\Domain\Interfaces\RestaurantRepositoryInterface;
use App\Shared\Domain\ValueObject\Email;
use App\User\Domain\Interfaces\PasswordHasherInterface;
use App\User\Domain\Interfaces\UserRepositoryInterface;

class AuthenticateForDeviceLink
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private RestaurantRepositoryInterface $restaurantRepository,
        private PasswordHasherInterface $passwordHasher,
    ) {}

    public function __invoke(string $email, string $plainPassword): AuthenticateForDeviceLinkResponse
    {
        $emailVO = Email::create($email);
        $user = $this->userRepository->findByEmail($emailVO->value());

        if ($user === null) {
            return AuthenticateForDeviceLinkResponse::notFound();
        }

        $isValidPassword = $this->passwordHasher->verify($plainPassword, $user->passwordHash()->value());

        if (! $isValidPassword) {
            return AuthenticateForDeviceLinkResponse::invalidCredentials();
        }

        $role = $user->role();

        if ($role !== 'admin') {
            return AuthenticateForDeviceLinkResponse::forbidden();
        }

        $restaurantId = null;
        $restaurantName = null;

        if (is_numeric($user->restaurantId())) {
            $restaurant = $this->restaurantRepository->findByInternalId((int) $user->restaurantId());

            if ($restaurant !== null) {
                $restaurantId = $restaurant->getUuid()->value();
                $restaurantName = $restaurant->getName()->value();
            }
        }

        return AuthenticateForDeviceLinkResponse::authenticated(
            $user->id()->value(),
            $user->name()->value(),
            $user->email()->value(),
            $restaurantId,
            $restaurantName,
        );
    }
}
