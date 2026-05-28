<?php

declare(strict_types=1);

namespace App\Audit\Domain;

use App\Audit\Domain\Entity\AuditLog;

final readonly class AuditLogPage
{
    /**
     * @param  list<AuditLog>  $items
     */
    public function __construct(
        public array $items,
        public ?\DateTimeImmutable $nextCursorCreatedAt,
        public ?int $nextCursorInternalId,
        public bool $hasMore,
    ) {}

    public static function empty(): self
    {
        return new self([], null, null, false);
    }
}
