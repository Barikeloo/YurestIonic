<?php

namespace App\Restaurant\Application\UpdateRestaurant;

use App\Restaurant\Domain\Interfaces\RestaurantRepositoryInterface;
use App\Shared\Domain\ValueObject\Email;
use App\User\Domain\Interfaces\PasswordHasherInterface;
use App\User\Domain\Interfaces\UserRepositoryInterface;

final class UpdateRestaurant
{
    public function __construct(
        private readonly RestaurantRepositoryInterface $restaurantRepository,
        private readonly PasswordHasherInterface $passwordHasher,
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function __invoke(
        string $id,
        ?string $name = null,
        ?string $legalName = null,
        ?string $taxId = null,
        ?string $email = null,
        ?string $plainPassword = null,
    ): ?UpdateRestaurantResponse {
        $restaurant = $this->restaurantRepository->getById($id);

        if ($restaurant === null) {
            return null;
        }

        if ($name !== null) {
            $restaurant->updateName($name);
        }

        if ($legalName !== null) {
            $restaurant->updateLegalName($legalName);
        }

        if ($taxId !== null) {
            $restaurant->updateTaxId($taxId);
        }

        if ($email !== null) {
            $restaurant->updateEmail(Email::create($email));
        }

        $passwordHash = null;

        if ($plainPassword !== null) {
            $passwordHash = $this->passwordHasher->hash($plainPassword);
            $restaurant->updatePassword($passwordHash);
        }

        $this->restaurantRepository->save($restaurant);

        if ($email !== null || $passwordHash !== null) {
            $this->userRepository->syncAdminCredentialsForRestaurant(
                restaurantUuid: $restaurant->getId()->value(),
                email: $email,
                passwordHash: $passwordHash,
            );
        }

        return UpdateRestaurantResponse::create($restaurant);
    }
}
