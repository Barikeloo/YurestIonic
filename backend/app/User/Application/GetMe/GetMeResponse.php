<?php

namespace App\User\Application\GetMe;

use App\User\Domain\Entity\User;

class GetMeResponse
{
    private function __construct(
        private string $id,
        private string $name,
        private string $email,
        private ?string $role,
        private ?string $restaurantId,
        private ?string $restaurantName,
    ) {}

    public static function create(User $user, ?string $role, ?string $restaurantId, ?string $restaurantName): self
    {
        return new self(
            $user->id()->value(),
            $user->name(),
            $user->email()->value(),
            $role,
            $restaurantId,
            $restaurantName,
        );
    }

    public function toArray(): array
    {
        return [
            'success' => true,
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'restaurant_id' => $this->restaurantId,
            'restaurant_name' => $this->restaurantName,
        ];
    }
}
