<?php

namespace App\User\Application\UpdateRestaurantUser;

final readonly class UpdateRestaurantUserResponse
{
    public function __construct(
        public string $uuid,
    ) {}

    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
        ];
    }
}
