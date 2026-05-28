<?php

declare(strict_types=1);

namespace App\Audit\Domain\Exception;

final class AuditLogNotFoundException extends \DomainException
{
    public static function withUuid(string $uuid): self
    {
        return new self("Audit log with uuid {$uuid} not found.");
    }
}
