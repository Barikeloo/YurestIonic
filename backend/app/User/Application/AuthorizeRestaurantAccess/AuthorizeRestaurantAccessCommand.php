<?php

namespace App\User\Application\AuthorizeRestaurantAccess;

final readonly class AuthorizeRestaurantAccessCommand
{
    public function __construct(
        public string $authUserUuid,
        public string $targetRestaurantUuid,
    ) {}
}
