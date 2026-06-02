<?php

namespace App\Restaurant\Application\CreateRestaurant;

final readonly class CreateRestaurantCommand
{
    public function __construct(
        public string $name,
        public ?string $legalName,
        public string $taxId,
        public string $email,
        public string $password,
        public ?string $pin,
        public string $companyMode,
        public ?string $deviceId = null,
        public ?string $ipAddress = null,
    ) {}
}
