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

    public static function hydrate(
        Uuid $id,
        Uuid $uuid,
        RestaurantName $name,
        ?RestaurantLegalName $legalName,
        ?RestaurantTaxId $taxId,
        Email $email,
        RestaurantPasswordHash $password,
        DomainDateTime $createdAt,
        DomainDateTime $updatedAt,
        ?DomainDateTime $deletedAt = null,
    ): self {
        return new self(
            id: $id,
            uuid: $uuid,
            name: $name,
            legalName: $legalName,
            taxId: $taxId,
            email: $email,
            password: $password,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
            deletedAt: $deletedAt,
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

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getUuid(): Uuid
    {
        return $this->uuid;
    }

    public function getName(): RestaurantName
    {
        return $this->name;
    }

    public function getLegalName(): ?RestaurantLegalName
    {
        return $this->legalName;
    }

    public function getTaxId(): ?RestaurantTaxId
    {
        return $this->taxId;
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function getPassword(): RestaurantPasswordHash
    {
        return $this->password;
    }

    public function getCreatedAt(): DomainDateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DomainDateTime
    {
        return $this->updatedAt;
    }

    public function getDeletedAt(): ?DomainDateTime
    {
        return $this->deletedAt;
    }
}
