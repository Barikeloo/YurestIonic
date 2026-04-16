<?php

namespace App\Restaurant\Application\ListRestaurants;

use App\Restaurant\Domain\Entity\Restaurant;

final class ListRestaurantsResponse
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly ?string $legal_name,
        public readonly ?string $tax_id,
        public readonly string $email,
    ) {}

    public static function create(Restaurant $restaurant): self
    {
        return new self(
            id: $restaurant->id()->value(),
            name: $restaurant->name()->value(),
            legal_name: $restaurant->legalName()?->value(),
            tax_id: $restaurant->taxId()?->value(),
            email: $restaurant->email()->value(),
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
