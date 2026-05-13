<?php

namespace App\Restaurant\Application\GetAdminRestaurantCollection;

final readonly class GetAdminRestaurantCollectionCommand
{
    public function __construct(
        public ?string $authUserUuid,
        public bool $isSuperAdmin,
    ) {}
}
