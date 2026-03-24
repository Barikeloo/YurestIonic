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
    ) {}

    public static function create(Restaurant $restaurant): self
    {
        return new self(
            id: $restaurant->getId()->value(),
            uuid: $restaurant->getUuid()->value(),
            name: $restaurant->getName(),
            legalName: $restaurant->getLegalName(),
            taxId: $restaurant->getTaxId(),
            email: $restaurant->getEmail()->value(),
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
        ];
    }
}
