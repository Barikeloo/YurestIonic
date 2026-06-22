<?php

declare(strict_types=1);

namespace App\GuestOrder\Domain\ReadModel;

final readonly class RoundData
{
    public function __construct(
        public string $roundId,
        public int $roundNumber,
        public ?string $label,
        public string $submittedAt,
        public array $lines,
    ) {}
}
