<?php

namespace App\Restaurant\Application\UpdateRestaurant;

use App\Restaurant\Domain\Event\RestaurantPasswordChanged;
use App\Restaurant\Domain\Event\RestaurantUpdated;
use App\Restaurant\Domain\Exception\CannotUpdateLegalDataException;
use App\Restaurant\Domain\Exception\ForbiddenException;
use App\Restaurant\Domain\Exception\LinkedRestaurantNotFoundException;
use App\Restaurant\Domain\Exception\NotAuthenticatedException;
use App\Restaurant\Domain\Exception\RestaurantNotFoundException;
use App\Restaurant\Domain\Interfaces\RestaurantRepositoryInterface;
use App\Restaurant\Domain\ValueObject\RestaurantLegalName;
use App\Restaurant\Domain\ValueObject\RestaurantName;
use App\Restaurant\Domain\ValueObject\RestaurantPasswordHash;
use App\Restaurant\Domain\ValueObject\RestaurantTaxId;
use App\Shared\Application\Event\EventBusInterface;
use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\Interfaces\PasswordHasherInterface;
use App\User\Domain\Interfaces\UserRepositoryInterface;

final class UpdateRestaurant
{
    public function __construct(
        private readonly RestaurantRepositoryInterface $restaurantRepository,
        private readonly PasswordHasherInterface $passwordHasher,
        private readonly UserRepositoryInterface $userRepository,
        private readonly EventBusInterface $eventBus,
    ) {}

    public function __invoke(UpdateRestaurantCommand $command): UpdateRestaurantResponse
    {

        if (! $command->isSuperAdmin) {
            if (! is_string($command->authUserUuid) || $command->authUserUuid === '') {
                throw NotAuthenticatedException::create();
            }

            $user = $this->userRepository->findById($command->authUserUuid);

            if ($user === null || $user->role() === null || ! $user->role()->isAdmin() || $user->restaurantId() === null) {
                throw ForbiddenException::create();
            }

            $linkedRestaurant = $this->restaurantRepository->findByInternalId($user->restaurantId()->toInt());
            $targetRestaurant = $this->restaurantRepository->findByUuid(Uuid::create($command->id));

            if ($linkedRestaurant === null) {
                throw LinkedRestaurantNotFoundException::create();
            }

            if ($targetRestaurant === null) {
                throw RestaurantNotFoundException::withUuid($command->id);
            }

            $linkedTaxId = $linkedRestaurant->taxId()?->value();

            if (! is_string($linkedTaxId) || $linkedTaxId === '') {
                if ($targetRestaurant->uuid()->value() !== $linkedRestaurant->uuid()->value()) {
                    throw ForbiddenException::create();
                }
            } else {
                if ($targetRestaurant->taxId()?->value() !== $linkedTaxId) {
                    throw ForbiddenException::create();
                }
            }

            if ($command->legalName !== null || $command->taxId !== null) {
                throw CannotUpdateLegalDataException::create();
            }
        }

        $restaurant = $this->restaurantRepository->getById($command->id);

        if ($restaurant === null) {
            throw RestaurantNotFoundException::withUuid($command->id);
        }

        $before = [
            'name' => $restaurant->name()->value(),
            'legal_name' => $restaurant->legalName()?->value(),
            'tax_id' => $restaurant->taxId()?->value(),
            'email' => $restaurant->email()->value(),
        ];

        if ($command->name !== null) {
            $restaurant->updateName(RestaurantName::create($command->name));
        }

        if ($command->legalName !== null) {
            $restaurant->updateLegalName(RestaurantLegalName::createNullable($command->legalName));
        }

        if ($command->taxId !== null) {
            $restaurant->updateTaxId(RestaurantTaxId::createNullable($command->taxId));
        }

        if ($command->email !== null) {
            $restaurant->updateEmail(Email::create($command->email));
        }

        $passwordHash = null;

        if ($command->plainPassword !== null) {
            $passwordHash = $this->passwordHasher->hash($command->plainPassword);
            $restaurant->updatePassword(RestaurantPasswordHash::create($passwordHash));
        }

        $this->restaurantRepository->save($restaurant);

        $after = [
            'name' => $restaurant->name()->value(),
            'legal_name' => $restaurant->legalName()?->value(),
            'tax_id' => $restaurant->taxId()?->value(),
            'email' => $restaurant->email()->value(),
        ];

        $events = [new RestaurantUpdated(
            restaurantUuid: $restaurant->uuid()->value(),
            before: $before,
            after: $after,
        )];

        if ($command->plainPassword !== null) {
            $events[] = new RestaurantPasswordChanged($restaurant->uuid()->value());
        }

        $this->eventBus->publish(...$events);

        if ($command->email !== null || $passwordHash !== null) {
            $this->userRepository->syncAdminCredentialsForRestaurant(
                restaurantUuid: $restaurant->id()->value(),
                email: $command->email,
                passwordHash: $passwordHash,
            );
        }

        return UpdateRestaurantResponse::fromRestaurant($restaurant);
    }
}
