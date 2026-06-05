<?php

declare(strict_types=1);

namespace App\Audit\Domain\ValueObject;

final readonly class AnomalyKindCount
{
    public function __construct(
        public string $kind,
        public int $count,
    ) {}
}
