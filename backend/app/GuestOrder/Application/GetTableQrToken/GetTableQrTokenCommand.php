<?php

declare(strict_types=1);

namespace App\GuestOrder\Application\GetTableQrToken;

final readonly class GetTableQrTokenCommand
{
    public function __construct(
        public string $tableId,
        public string $restaurantId,
    ) {}
}
