<?php

declare(strict_types=1);

namespace App\Family\Application\ListActiveFamilies;

use App\Family\Application\ListFamilies\ListFamilies;

/**
 * List only active families for TPV use.
 * Delegates to ListFamilies with onlyActive=true.
 */
final class ListActiveFamilies
{
    public function __construct(
        private ListFamilies $listFamilies,
    ) {}

    /**
     * @return array<int, array<string, bool|string>>
     */
    public function __invoke(): array
    {
        return ($this->listFamilies)(includeDeleted: false, onlyActive: true);
    }
}
