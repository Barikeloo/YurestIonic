<?php

declare(strict_types=1);

namespace App\Family\Application\ListActiveFamilies;

use App\Family\Application\ListFamilies\ListFamilies;

final class ListActiveFamilies
{
    public function __construct(
        private ListFamilies $listFamilies,
    ) {}

    public function __invoke(): array
    {
        return ($this->listFamilies)(includeDeleted: false, onlyActive: true);
    }
}
