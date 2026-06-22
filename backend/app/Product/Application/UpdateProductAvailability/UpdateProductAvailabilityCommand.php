<?php

declare(strict_types=1);

namespace App\Product\Application\UpdateProductAvailability;

final readonly class UpdateProductAvailabilityCommand
{
    public function __construct(
        public string $productId,
        public bool $available,
    ) {}
}
