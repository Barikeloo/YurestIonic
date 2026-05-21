<?php

declare(strict_types=1);

namespace App\Menu\Application\ArchiveMenu;

use App\Menu\Domain\Exception\MenuNotFoundException;
use App\Menu\Domain\Interfaces\MenuRepositoryInterface;

class ArchiveMenu
{
    public function __construct(
        private MenuRepositoryInterface $menuRepository,
    ) {}

    public function __invoke(ArchiveMenuCommand $command): void
    {
        $menu = $this->menuRepository->findById($command->id, includeArchived: true)
            ?? throw MenuNotFoundException::withId($command->id);

        if ($menu->isArchived()) {
            return;
        }

        $menu->archive();
        $this->menuRepository->save($menu);
    }
}
