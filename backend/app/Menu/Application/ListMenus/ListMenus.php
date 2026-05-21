<?php

declare(strict_types=1);

namespace App\Menu\Application\ListMenus;

use App\Menu\Application\Shared\MenuView;
use App\Menu\Domain\Interfaces\MenuRepositoryInterface;

class ListMenus
{
    public function __construct(
        private MenuRepositoryInterface $menuRepository,
    ) {}

    public function __invoke(ListMenusCommand $command): ListMenusResponse
    {
        $filters = [];
        if ($command->active !== null) {
            $filters['active'] = $command->active;
        }
        if ($command->archived !== null) {
            $filters['archived'] = $command->archived;
        }
        if ($command->search !== null && $command->search !== '') {
            $filters['search'] = $command->search;
        }

        $menus = $this->menuRepository->findAllByCurrentRestaurant($filters);

        $items = array_map(static fn ($menu): array => MenuView::toArray($menu), $menus);

        return ListMenusResponse::create($items);
    }
}
