<?php

namespace App\User\Application\GetMe;

final readonly class GetMeResponse
{
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
        public ?string $role,
        public ?string $restaurantId,
        public ?string $restaurantName,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'restaurant_id' => $this->restaurantId,
            'restaurant_name' => $this->restaurantName,
        ];
    }
}
