<?php

namespace App\User\Application\CreateUser;

final readonly class CreateUserResponse
{
    private function __construct(
        public string $id,
        public string $name,
        public string $email,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public static function create(
        string $id,
        string $name,
        string $email,
        string $createdAt,
        string $updatedAt,
    ): self {
        return new self(
            id: $id,
            name: $name,
            email: $email,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
