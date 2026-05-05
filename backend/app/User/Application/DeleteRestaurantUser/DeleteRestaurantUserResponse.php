<?php

namespace App\User\Application\DeleteRestaurantUser;

final readonly class DeleteRestaurantUserResponse
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
