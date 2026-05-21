<?php

declare(strict_types=1);

namespace App\Menu\Domain\Interfaces;

use App\Menu\Domain\Entity\Menu;

interface MenuRepositoryInterface
{
    /**
     * Persiste el aggregate completo (menu + secciones + items) en una transacción.
     * Reemplaza por completo las secciones existentes.
     */
    public function save(Menu $menu): void;

    public function findById(string $id, bool $includeArchived = true): ?Menu;

    /**
     * Lista los menús del restaurante actual (tenant).
     *
     * @param  array{ active?: bool, archived?: bool, search?: string|null }  $filters
     * @return Menu[]
     */
    public function findAllByCurrentRestaurant(array $filters = []): array;

    public function existsById(string $id): bool;
}
