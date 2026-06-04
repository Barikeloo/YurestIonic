<?php

declare(strict_types=1);

namespace App\Audit\Domain\ValueObject;

/**
 * Count of archived audit logs whose ORIGINAL created_at falls inside a
 * given calendar month. Used to show "how far back the archive goes" in
 * the admin history panel.
 */
final readonly class MonthlyArchivedCount
{
    public function __construct(
        public string $month,
        public int $count,
    ) {}
}
