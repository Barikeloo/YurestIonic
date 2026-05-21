<?php

declare(strict_types=1);

namespace App\Menu\Application\SetMenuActive;

use App\Menu\Domain\Exception\MenuNotFoundException;
use App\Menu\Domain\Interfaces\MenuRepositoryInterface;

class SetMenuActive
{
    public function __construct(
        private MenuRepositoryInterface $menuRepository,
    ) {}

    public function __invoke(SetMenuActiveCommand $command): SetMenuActiveResponse
    {
        $menu = $this->menuRepository->findById($command->id, includeArchived: false)
            ?? throw MenuNotFoundException::withId($command->id);

        if ($command->active) {
            $menu->activate();
        } else {
            $menu->deactivate();
        }

        $this->menuRepository->save($menu);

        return SetMenuActiveResponse::fromEntity($menu);
    }
}
