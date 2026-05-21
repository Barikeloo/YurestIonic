<?php

declare(strict_types=1);

namespace App\Menu\Application\ListMenus;

final readonly class ListMenusResponse
{
    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function __construct(public array $items) {}

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    public static function create(array $items): self
    {
        return new self($items);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function toArray(): array
    {
        return $this->items;
    }
}
