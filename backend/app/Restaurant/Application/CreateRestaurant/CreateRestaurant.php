<?php

namespace App\Restaurant\Application\CreateRestaurant;

use App\Restaurant\Domain\Entity\Restaurant;
use App\Restaurant\Domain\Exception\NotSuperAdminException;
use App\Restaurant\Domain\Exception\TaxIdAlreadyExistsException;
use App\Restaurant\Domain\Exception\TaxIdDoesNotExistException;
use App\Restaurant\Domain\Interfaces\RestaurantRepositoryInterface;
use App\Restaurant\Domain\ValueObject\RestaurantLegalName;
use App\Restaurant\Domain\ValueObject\RestaurantName;
use App\Restaurant\Domain\ValueObject\RestaurantPasswordHash;
use App\Restaurant\Domain\ValueObject\RestaurantTaxId;
use App\Shared\Application\Event\EventBusInterface;
use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Application\CreateRestaurantUser\CreateRestaurantUser;
use App\User\Application\CreateRestaurantUser\CreateRestaurantUserCommand;
use App\User\Domain\Exception\PinAlreadyInUseException;

final class CreateRestaurant
{
    public function __construct(
        private readonly RestaurantRepositoryInterface $restaurantRepository,
        private readonly CreateRestaurantUser $createRestaurantUser,
        private readonly EventBusInterface $eventBus,
    ) {}

    public function __invoke(CreateRestaurantCommand $command, ?string $superAdminUuid): CreateRestaurantResponse
    {
        if ($superAdminUuid === null || $superAdminUuid === '') {
            throw NotSuperAdminException::create();
        }

        $taxId = trim($command->taxId);
        $companyMode = $command->companyMode;

        $companyExists = count($this->restaurantRepository->findByTaxId($taxId)) > 0;

        if ($companyMode === 'new' && $companyExists) {
            throw TaxIdAlreadyExistsException::create($taxId);
        }

        if ($companyMode === 'existing' && ! $companyExists) {
            throw TaxIdDoesNotExistException::create($taxId);
        }

        $restaurant = Restaurant::dddCreate(
            id: Uuid::generate(),
            name: RestaurantName::create($command->name),
            legalName: RestaurantLegalName::createNullable($command->legalName),
            taxId: RestaurantTaxId::createNullable($taxId),
            email: Email::create($command->email),
            password: RestaurantPasswordHash::create($command->password),
        );

        $this->restaurantRepository->save($restaurant);

        $this->eventBus->publish(...$restaurant->pullDomainEvents());

        $adminPin = $command->pin ?? str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);

        try {
            ($this->createRestaurantUser)(new CreateRestaurantUserCommand(
                name: $command->name,
                email: $command->email,
                plainPassword: $command->password,
                restaurantUuid: $restaurant->uuid()->value(),
                role: 'admin',
                plainPin: $adminPin,
            ));
        } catch (PinAlreadyInUseException $e) {
            throw $e;
        }

        return CreateRestaurantResponse::fromRestaurant($restaurant, $adminPin);
    }
}
