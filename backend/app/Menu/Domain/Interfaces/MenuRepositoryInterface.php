<?php

declare(strict_types=1);

namespace App\Menu\Domain\Interfaces;

use App\Menu\Domain\Entity\Menu;

interface MenuRepositoryInterface
{

    public function save(Menu $menu): void;

    public function findById(string $id, bool $includeArchived = true): ?Menu;

    public function findAllByCurrentRestaurant(array $filters = []): array;

    public function existsById(string $id): bool;
}
