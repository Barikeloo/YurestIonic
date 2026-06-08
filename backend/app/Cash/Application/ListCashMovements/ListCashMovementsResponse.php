<?php

declare(strict_types=1);

namespace App\Cash\Application\ListCashMovements;

final readonly class ListCashMovementsResponse
{

    private function __construct(
        public array $movements,
    ) {}

    public static function create(array $movements): self
    {
        return new self(
            movements: $movements,
        );
    }

    public function toArray(): array
    {
        return [
            'movements' => array_map(
                static fn (ListCashMovementsItemResponse $item): array => $item->toArray(),
                $this->movements,
            ),
        ];
    }
}
