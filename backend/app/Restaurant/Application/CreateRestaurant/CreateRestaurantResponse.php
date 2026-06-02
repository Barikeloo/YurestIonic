<?php

namespace App\Restaurant\Application\CreateRestaurant;

use App\Restaurant\Domain\Entity\Restaurant;

final class CreateRestaurantResponse
{
    private function __construct(
        public readonly string $id,
        public readonly string $uuid,
        public readonly string $name,
        public readonly ?string $legalName,
        public readonly ?string $taxId,
        public readonly string $email,
        public readonly ?string $adminPin = null,
    ) {}

    public static function create(
        string $id,
        string $uuid,
        string $name,
        ?string $legalName,
        ?string $taxId,
        string $email,
        ?string $adminPin = null,
    ): self {
        return new self(
            id: $id,
            uuid: $uuid,
            name: $name,
            legalName: $legalName,
            taxId: $taxId,
            email: $email,
            adminPin: $adminPin,
        );
    }

    public static function fromRestaurant(Restaurant $restaurant, ?string $adminPin = null): self
    {
        return new self(
            id: $restaurant->id()->value(),
            uuid: $restaurant->uuid()->value(),
            name: $restaurant->name()->value(),
            legalName: $restaurant->legalName()?->value(),
            taxId: $restaurant->taxId()?->value(),
            email: $restaurant->email()->value(),
            adminPin: $adminPin,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'legal_name' => $this->legalName,
            'tax_id' => $this->taxId,
            'email' => $this->email,
            'admin_pin' => $this->adminPin,
        ];
    }
}
