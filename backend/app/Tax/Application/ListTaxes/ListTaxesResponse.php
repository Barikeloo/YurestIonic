<?php

namespace App\Tax\Application\ListTaxes;

final readonly class ListTaxesResponse
{

    private function __construct(
        public array $items,
    ) {}

    public static function create(array $items): self
    {
        return new self(
            items: $items,
        );
    }

    public function toArray(): array
    {
        return $this->items;
    }
}
