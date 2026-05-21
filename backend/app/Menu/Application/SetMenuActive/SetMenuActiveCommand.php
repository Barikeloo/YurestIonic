<?php

declare(strict_types=1);

namespace App\Menu\Application\SetMenuActive;

final readonly class SetMenuActiveCommand
{
    public function __construct(
        public string $id,
        public bool $active,
    ) {}
}
