<?php

declare(strict_types=1);

namespace App\Menu\Application\ListMenus;

final readonly class ListMenusResponse
{

    private function __construct(public array $items) {}

    public static function create(array $items): self
    {
        return new self($items);
    }

    public function toArray(): array
    {
        return $this->items;
    }
}
