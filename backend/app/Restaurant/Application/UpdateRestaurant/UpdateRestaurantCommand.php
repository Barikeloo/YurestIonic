<?php

namespace App\Restaurant\Application\UpdateRestaurant;

final readonly class UpdateRestaurantCommand
{
    public function __construct(
        public string $id,
        public ?string $name,
        public ?string $legalName,
        public ?string $taxId,
        public ?string $email,
        public ?string $plainPassword,
        public ?string $authUserUuid,
        public bool $isSuperAdmin,
    ) {}
}
