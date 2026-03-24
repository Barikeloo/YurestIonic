<?php

namespace App\Restaurant\Application\UpdateRestaurant;

use App\Restaurant\Domain\Entity\Restaurant;

final class UpdateRestaurantResponse
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $legal_name,
        public readonly string $tax_id,
        public readonly string $email,
    ) {}

    public static function create(Restaurant $restaurant): self
    {
        return new self(
            id: $restaurant->getId()->value(),
            name: $restaurant->getName(),
            legal_name: $restaurant->getLegalName(),
            tax_id: $restaurant->getTaxId(),
            email: $restaurant->getEmail()->value(),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'legal_name' => $this->legal_name,
            'tax_id' => $this->tax_id,
            'email' => $this->email,
        ];
    }
}
