<?php

namespace App\Restaurant\Application\RegisterRestaurantWithAdmin;

use App\Restaurant\Domain\Entity\Restaurant;
use App\Restaurant\Domain\Interfaces\RestaurantRepositoryInterface;
use App\Shared\Domain\Interfaces\TransactionManagerInterface;
use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\Interfaces\PasswordHasherInterface;
use App\User\Domain\Interfaces\UserRepositoryInterface;

final class RegisterRestaurantWithAdmin
{
    public function __construct(
        private readonly RestaurantRepositoryInterface $restaurantRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly PasswordHasherInterface $passwordHasher,
        private readonly TransactionManagerInterface $transactionManager,
    ) {}

    public function __invoke(
        string $restaurantName,
        ?string $legalName,
        ?string $taxId,
        string $email,
        string $plainPassword,
        ?string $adminName = null,
        ?string $adminPin = null,
    ): RegisterRestaurantWithAdminResponse {
        $emailVO = Email::create($email);
        $hashedPassword = $this->passwordHasher->hash($plainPassword);
        $effectiveAdminPin = $adminPin;

        if ($effectiveAdminPin === null || trim($effectiveAdminPin) === '') {
            $effectiveAdminPin = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        }

        $hashedPin = $this->passwordHasher->hash($effectiveAdminPin);

        $restaurant = Restaurant::dddCreate(
            id: Uuid::generate(),
            name: $restaurantName,
            legalName: $legalName,
            taxId: $taxId,
            email: $emailVO,
            password: $hashedPassword,
        );

        $effectiveAdminName = $adminName;

        if ($effectiveAdminName === null || trim($effectiveAdminName) === '') {
            $effectiveAdminName = sprintf('Admin %s', $restaurantName);
        }

        $this->transactionManager->run(function () use ($restaurant, $effectiveAdminName, $emailVO, $hashedPassword, $hashedPin): void {
            $this->restaurantRepository->save($restaurant);
            $this->userRepository->saveAdminForRestaurant(
                restaurantUuid: $restaurant->getUuid()->value(),
                name: $effectiveAdminName,
                email: $emailVO->value(),
                passwordHash: $hashedPassword,
                pinHash: $hashedPin,
            );
        });

        return RegisterRestaurantWithAdminResponse::create(
            restaurantId: $restaurant->getUuid()->value(),
            restaurantName: $restaurant->getName(),
            adminEmail: $emailVO->value(),
            adminName: $effectiveAdminName,
            adminPin: $effectiveAdminPin,
        );
    }
}