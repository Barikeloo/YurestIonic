<?php

namespace App\Family\Application\ListFamilies;

final readonly class ListFamiliesResponse
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
