<?php

declare(strict_types=1);

namespace App\Menu\Application\GetMenu;

use App\Menu\Domain\Exception\MenuNotFoundException;
use App\Menu\Domain\Interfaces\MenuRepositoryInterface;

class GetMenu
{
    public function __construct(
        private MenuRepositoryInterface $menuRepository,
    ) {}

    public function __invoke(GetMenuCommand $command): GetMenuResponse
    {
        $menu = $this->menuRepository->findById($command->id, includeArchived: true)
            ?? throw MenuNotFoundException::withId($command->id);

        return GetMenuResponse::fromEntity($menu);
    }
}
