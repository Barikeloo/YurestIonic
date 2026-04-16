<?php

namespace App\SuperAdmin\Domain\Entity;

use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\Uuid;
use App\SuperAdmin\Domain\ValueObject\SuperAdminName;
use App\SuperAdmin\Domain\ValueObject\SuperAdminPasswordHash;

final class SuperAdmin
{
    private function __construct(
        private Uuid $id,
        private SuperAdminName $name,
        private Email $email,
        private SuperAdminPasswordHash $passwordHash,
        private DomainDateTime $createdAt,
        private DomainDateTime $updatedAt,
    ) {}

    public static function dddCreate(
        Uuid $id,
        SuperAdminName $name,
        Email $email,
        SuperAdminPasswordHash $passwordHash,
    ): self {
        $now = DomainDateTime::now();

        return new self($id, $name, $email, $passwordHash, $now, $now);
    }

    public static function fromPersistence(
        string $id,
        string $name,
        string $email,
        string $passwordHash,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            Uuid::create($id),
            SuperAdminName::create($name),
            Email::create($email),
            SuperAdminPasswordHash::create($passwordHash),
            DomainDateTime::create($createdAt),
            DomainDateTime::create($updatedAt),
        );
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function name(): SuperAdminName
    {
        return $this->name;
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function passwordHash(): SuperAdminPasswordHash
    {
        return $this->passwordHash;
    }

    public function createdAt(): DomainDateTime
    {
        return $this->createdAt;
    }

    public function updatedAt(): DomainDateTime
    {
        return $this->updatedAt;
    }
}
