<?php

declare(strict_types=1);

namespace App\GuestOrder\Application\DeletePendingLine;

final readonly class DeletePendingLineCommand
{
    public function __construct(
        public string $token,
        public string $sessionToken,
        public string $lineId,
    ) {}
}
