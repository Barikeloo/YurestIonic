<?php

declare(strict_types=1);

namespace App\Audit\Domain\Exception;

final class ForbiddenAuditAccessException extends \DomainException
{
    public static function includeArchivedNotAllowed(): self
    {
        return new self('Only administrators can include archived audit events.');
    }
}
