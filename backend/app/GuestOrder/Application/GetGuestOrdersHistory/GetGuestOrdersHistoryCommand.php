<?php

declare(strict_types=1);

namespace App\GuestOrder\Application\GetGuestOrdersHistory;

final readonly class GetGuestOrdersHistoryCommand
{
    public function __construct(
        public string $sessionToken,
    ) {}
}
