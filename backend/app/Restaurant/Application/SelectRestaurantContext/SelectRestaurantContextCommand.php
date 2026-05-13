<?php

namespace App\Restaurant\Application\SelectRestaurantContext;

final readonly class SelectRestaurantContextCommand
{
    public function __construct(
        public ?string $authUserUuid,
        public string $targetRestaurantUuid,
        public bool $isSuperAdmin,
    ) {}
}
