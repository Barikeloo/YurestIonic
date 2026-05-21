<?php

declare(strict_types=1);

namespace App\Menu\Application\UpdateMenu;

use App\Menu\Application\Shared\MenuSectionsBuilder;
use App\Menu\Domain\Exception\MenuNotFoundException;
use App\Menu\Domain\Interfaces\MenuRepositoryInterface;
use App\Menu\Domain\ValueObject\MenuAvailability;
use App\Menu\Domain\ValueObject\MenuDescription;
use App\Menu\Domain\ValueObject\MenuName;
use App\Menu\Domain\ValueObject\MenuPrice;
use App\Menu\Domain\ValueObject\MenuValidity;
use App\Shared\Domain\ValueObject\Uuid;

class UpdateMenu
{
    public function __construct(
        private MenuRepositoryInterface $menuRepository,
    ) {}

    public function __invoke(UpdateMenuCommand $command): UpdateMenuResponse
    {
        $menu = $this->menuRepository->findById($command->id, includeArchived: false)
            ?? throw MenuNotFoundException::withId($command->id);

        $validity = MenuValidity::create(
            $command->validityFrom !== null ? new \DateTimeImmutable($command->validityFrom) : null,
            $command->validityTo !== null ? new \DateTimeImmutable($command->validityTo) : null,
        );

        $availability = MenuAvailability::create(
            $command->availableDays,
            $command->availableFromTime,
            $command->availableToTime,
        );

        $menu->updateHeader(
            taxId: Uuid::create($command->taxId),
            name: MenuName::create($command->name),
            description: MenuDescription::create($command->description),
            price: MenuPrice::create($command->price),
            validity: $validity,
            availability: $availability,
            active: $command->active,
        );

        $sections = MenuSectionsBuilder::build($menu->id(), $command->sections);
        $menu->replaceSections($sections);

        $this->menuRepository->save($menu);

        return UpdateMenuResponse::fromEntity($menu);
    }
}
