<?php

namespace App\SuperAdmin\Domain\Entity;

final class SuperAdmin
{
    private function __construct(
        private string $id,
        private string $name,
        private string $email,
        private string $passwordHash,
    ) {}

    public static function fromPersistence(
        string $id,
        string $name,
        string $email,
        string $passwordHash,
    ): self {
        return new self($id, $name, $email, $passwordHash);
    }

    public function id(): string
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function passwordHash(): string
    {
        return $this->passwordHash;
    }
}
