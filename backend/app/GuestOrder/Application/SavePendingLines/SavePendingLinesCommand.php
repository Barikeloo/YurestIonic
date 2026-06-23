<?php

declare(strict_types=1);

namespace App\GuestOrder\Application\SavePendingLines;

use App\GuestOrder\Domain\ValueObject\GuestLineInput;

final readonly class SavePendingLinesCommand
{
    public function __construct(
        public string $token,
        public string $sessionToken,
        public array $lines,
    ) {}
}
