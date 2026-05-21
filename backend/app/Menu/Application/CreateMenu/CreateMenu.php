<?php

declare(strict_types=1);

namespace App\Menu\Application\CreateMenu;

use App\Menu\Application\Shared\MenuSectionsBuilder;
use App\Menu\Domain\Entity\Menu;
use App\Menu\Domain\Interfaces\MenuRepositoryInterface;
use App\Menu\Domain\ValueObject\MenuAvailability;
use App\Menu\Domain\ValueObject\MenuDescription;
use App\Menu\Domain\ValueObject\MenuName;
use App\Menu\Domain\ValueObject\MenuPrice;
use App\Menu\Domain\ValueObject\MenuValidity;
use App\Shared\Domain\ValueObject\Uuid;

class CreateMenu
{
    public function __construct(
        private MenuRepositoryInterface $menuRepository,
    ) {}

    public function __invoke(CreateMenuCommand $command): CreateMenuResponse
    {
        $validity = MenuValidity::create(
            $command->validityFrom !== null ? new \DateTimeImmutable($command->validityFrom) : null,
            $command->validityTo !== null ? new \DateTimeImmutable($command->validityTo) : null,
        );

        $availability = MenuAvailability::create(
            $command->availableDays,
            $command->availableFromTime,
            $command->availableToTime,
        );

        // Generamos el id del menú antes para que las secciones lo conozcan.
        $menuId = Uuid::generate();
        $sections = MenuSectionsBuilder::build($menuId, $command->sections);

        $menu = Menu::dddCreate(
            taxId: Uuid::create($command->taxId),
            name: MenuName::create($command->name),
            description: MenuDescription::create($command->description),
            price: MenuPrice::create($command->price),
            validity: $validity,
            availability: $availability,
            active: $command->active,
            sections: $sections,
        );

        $this->menuRepository->save($menu);

        return CreateMenuResponse::fromEntity($menu);
    }
}
