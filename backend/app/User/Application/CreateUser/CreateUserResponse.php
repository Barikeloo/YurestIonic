<?php

namespace App\User\Application\CreateUser;

final readonly class CreateUserResponse
{
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    /**
     * @return array<string, string>
     */
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
