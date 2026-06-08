<?php

namespace App\User\Application\AuthenticateUser;

final readonly class AuthenticateUserResponse
{
    private function __construct(
        public string $id,
        public string $name,
        public string $email,
        public ?string $role = null,
        public ?string $restaurantId = null,
        public ?string $restaurantName = null,
    ) {}

    public static function create(
        string $id,
        string $name,
        string $email,
        ?string $role = null,
        ?string $restaurantId = null,
        ?string $restaurantName = null,
    ): self {
        return new self(
            id: $id,
            name: $name,
            email: $email,
            role: $role,
            restaurantId: $restaurantId,
            restaurantName: $restaurantName,
        );
    }

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
