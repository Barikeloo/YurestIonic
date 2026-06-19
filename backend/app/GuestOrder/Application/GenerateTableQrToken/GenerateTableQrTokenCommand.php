<?php

declare(strict_types=1);

namespace App\GuestOrder\Application\GenerateTableQrToken;

final readonly class GenerateTableQrTokenCommand
{
    public function __construct(
        public string $tableId,
        public string $restaurantId,
    ) {}
}
