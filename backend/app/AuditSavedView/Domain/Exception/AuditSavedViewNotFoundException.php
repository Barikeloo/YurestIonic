<?php

declare(strict_types=1);

namespace App\AuditSavedView\Domain\Exception;

final class AuditSavedViewNotFoundException extends \DomainException
{
    public static function withUuid(string $uuid): self
    {
        return new self("Audit saved view with UUID {$uuid} not found.");
    }
}
