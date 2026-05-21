<?php

declare(strict_types=1);

namespace App\Menu\Application\ListMenus;

final readonly class ListMenusCommand
{
    public function __construct(
        public ?bool $active = null,
        public ?bool $archived = null,
        public ?string $search = null,
    ) {}
}
