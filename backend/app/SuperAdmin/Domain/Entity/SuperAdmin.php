<?php

namespace App\SuperAdmin\Domain\Entity;

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
    ) {}

    public static function hydrate(
        Uuid $id,
        SuperAdminName $name,
        Email $email,
        SuperAdminPasswordHash $passwordHash,
    ): self {
        return new self($id, $name, $email, $passwordHash);
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
}
