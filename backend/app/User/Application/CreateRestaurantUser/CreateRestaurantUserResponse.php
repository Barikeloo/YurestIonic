<?php

namespace App\User\Application\CreateRestaurantUser;

final readonly class CreateRestaurantUserResponse
{
    public function __construct(
        public string $uuid,
        public string $name,
        public string $email,
        public string $role,
    ) {}

    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
        ];
    }
}
