<?php

namespace App\User\Application\UpdateRestaurantUser;

use App\User\Domain\Interfaces\PasswordHasherInterface;
use App\User\Domain\Interfaces\UserRepositoryInterface;

class UpdateRestaurantUser
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PasswordHasherInterface $passwordHasher,
    ) {}

    public function __invoke(
        string $uuid,
        ?string $name = null,
        ?string $email = null,
        ?string $plainPassword = null,
    ): UpdateRestaurantUserResponse {
        $user = $this->userRepository->findById($uuid);

        if ($user === null) {
            return UpdateRestaurantUserResponse::notFound();
        }

        $updates = [];

        if ($name !== null) {
            $updates['name'] = $name;
        }

        if ($email !== null) {
            $updates['email'] = $email;
        }

        if ($plainPassword !== null) {
            $updates['password'] = $this->passwordHasher->hash($plainPassword);
        }

        if (empty($updates)) {
            return UpdateRestaurantUserResponse::success($uuid);
        }

        $this->userRepository->updatePartial($uuid, $updates);

        return UpdateRestaurantUserResponse::success($uuid);
    }
}
