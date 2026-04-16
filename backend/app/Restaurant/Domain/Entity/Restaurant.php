<?php

namespace App\Restaurant\Domain\Entity;

use App\Restaurant\Domain\ValueObject\RestaurantLegalName;
use App\Restaurant\Domain\ValueObject\RestaurantName;
use App\Restaurant\Domain\ValueObject\RestaurantPasswordHash;
use App\Restaurant\Domain\ValueObject\RestaurantTaxId;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\Uuid;

final class Restaurant
{
    private function __construct(
        private readonly Uuid $id,
        private readonly Uuid $uuid,
        private RestaurantName $name,
        private ?RestaurantLegalName $legalName,
        private ?RestaurantTaxId $taxId,
        private Email $email,
        private RestaurantPasswordHash $password,
        private readonly DomainDateTime $createdAt,
        private DomainDateTime $updatedAt,
        private ?DomainDateTime $deletedAt = null,
    ) {
    }

    public static function dddCreate(
        Uuid $id,
        RestaurantName $name,
        ?RestaurantLegalName $legalName,
        ?RestaurantTaxId $taxId,
        Email $email,
        RestaurantPasswordHash $password,
    ): self {
        return new self(
            id: $id,
            uuid: $id,
            name: $name,
            legalName: $legalName,
            taxId: $taxId,
            email: $email,
            password: $password,
            createdAt: DomainDateTime::now(),
            updatedAt: DomainDateTime::now(),
        );
    }

    public static function fromPersistence(
        string $id,
        string $uuid,
        string $name,
        ?string $legalName,
        ?string $taxId,
        string $email,
        string $password,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
        ?\DateTimeImmutable $deletedAt = null,
    ): self {
        return new self(
            id: Uuid::create($id),
            uuid: Uuid::create($uuid),
            name: RestaurantName::create($name),
            legalName: $legalName !== null ? RestaurantLegalName::create($legalName) : null,
            taxId: $taxId !== null ? RestaurantTaxId::create($taxId) : null,
            email: Email::create($email),
            password: RestaurantPasswordHash::create($password),
            createdAt: DomainDateTime::create($createdAt),
            updatedAt: DomainDateTime::create($updatedAt),
            deletedAt: $deletedAt !== null ? DomainDateTime::create($deletedAt) : null,
        );
    }

    public function updateName(RestaurantName $name): void
    {
        $this->name = $name;
        $this->updatedAt = DomainDateTime::now();
    }

    public function updateLegalName(?RestaurantLegalName $legalName): void
    {
        $this->legalName = $legalName;
        $this->updatedAt = DomainDateTime::now();
    }

    public function updateTaxId(?RestaurantTaxId $taxId): void
    {
        $this->taxId = $taxId;
        $this->updatedAt = DomainDateTime::now();
    }

    public function updateEmail(Email $email): void
    {
        $this->email = $email;
        $this->updatedAt = DomainDateTime::now();
    }

    public function updatePassword(RestaurantPasswordHash $password): void
    {
        $this->password = $password;
        $this->updatedAt = DomainDateTime::now();
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function uuid(): Uuid
    {
        return $this->uuid;
    }

    public function name(): RestaurantName
    {
        return $this->name;
    }

    public function legalName(): ?RestaurantLegalName
    {
        return $this->legalName;
    }

    public function taxId(): ?RestaurantTaxId
    {
        return $this->taxId;
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function password(): RestaurantPasswordHash
    {
        return $this->password;
    }

    public function createdAt(): DomainDateTime
    {
        return $this->createdAt;
    }

    public function updatedAt(): DomainDateTime
    {
        return $this->updatedAt;
    }

    public function deletedAt(): ?DomainDateTime
    {
        return $this->deletedAt;
    }
}
