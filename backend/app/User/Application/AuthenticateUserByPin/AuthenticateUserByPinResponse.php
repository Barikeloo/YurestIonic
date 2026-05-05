<?php

namespace App\User\Application\AuthenticateUserByPin;

final readonly class AuthenticateUserByPinResponse
{
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
        public ?string $role = null,
        public ?string $restaurantId = null,
        public ?string $restaurantName = null,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'restaurantId' => $this->restaurantId,
            'restaurantName' => $this->restaurantName,
        ];
    }
}
