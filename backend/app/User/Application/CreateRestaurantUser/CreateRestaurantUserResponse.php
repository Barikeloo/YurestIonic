<?php

namespace App\User\Application\CreateRestaurantUser;

class CreateRestaurantUserResponse
{
    private function __construct(
        private string $uuid,
        private string $name,
        private string $email,
    ) {}

    public static function create(string $uuid, string $name, string $email): self
    {
        return new self($uuid, $name, $email);
    }

    public function uuid(): string
    {
        return $this->uuid;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'email' => $this->email,
        ];
    }
}
