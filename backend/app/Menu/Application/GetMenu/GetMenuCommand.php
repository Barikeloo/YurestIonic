<?php

declare(strict_types=1);

namespace App\Menu\Application\GetMenu;

final readonly class GetMenuCommand
{
    public function __construct(
        public string $id,
    ) {}
}
