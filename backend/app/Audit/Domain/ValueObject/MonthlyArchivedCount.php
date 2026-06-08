<?php

declare(strict_types=1);

namespace App\Audit\Domain\ValueObject;

final readonly class MonthlyArchivedCount
{
    public function __construct(
        public string $month,
        public int $count,
    ) {}
}
