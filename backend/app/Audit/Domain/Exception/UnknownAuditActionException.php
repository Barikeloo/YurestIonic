<?php

declare(strict_types=1);

namespace App\Audit\Domain\Exception;

final class UnknownAuditActionException extends \DomainException
{
    public static function withSlug(string $slug): self
    {
        return new self("Unknown audit action slug: {$slug}. Every recordable action must be declared in AuditEventCatalog.");
    }
}
