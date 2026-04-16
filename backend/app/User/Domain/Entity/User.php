<?php

namespace App\User\Domain\Entity;

use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\ValueObject\PasswordHash;
use App\User\Domain\ValueObject\RestaurantId;
use App\User\Domain\ValueObject\Role;
use App\User\Domain\ValueObject\UserName;

class User
{
    private function __construct(
        private Uuid $id,
        private UserName $name,
        private Email $email,
        private PasswordHash $passwordHash,
        private ?Role $role,
        private ?RestaurantId $restaurantId,
        private DomainDateTime $createdAt,
        private DomainDateTime $updatedAt,
    ) {}


    public static function dddCreate(Email $email, UserName $name, PasswordHash $passwordHash, ?Role $role = null, ?RestaurantId $restaurantId = null): self
    {
        $now = DomainDateTime::now();

        return new self(
            Uuid::generate(),
            $name,
            $email,
            $passwordHash,
            $role,
            $restaurantId,
            $now,
            $now,
        );
    }

    public static function fromPersistence(
        string $id,
        string $name,
        string $email,
        string $passwordHash,
        ?string $role,
        ?string $restaurantId,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            Uuid::create($id),
            UserName::create($name),
            Email::create($email),
            PasswordHash::create($passwordHash),
            $role !== null ? Role::create($role) : null,
            $restaurantId !== null ? RestaurantId::create($restaurantId) : null,
            DomainDateTime::create($createdAt),
            DomainDateTime::create($updatedAt),
        );
    }
    public function role(): ?Role
    {
        return $this->role;
    }

    public function restaurantId(): ?RestaurantId
    {
        return $this->restaurantId;
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function name(): UserName
    {
        return $this->name;
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function passwordHash(): PasswordHash
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
