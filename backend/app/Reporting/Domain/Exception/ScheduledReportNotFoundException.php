<?php

declare(strict_types=1);

namespace App\Reporting\Domain\Exception;

final class ScheduledReportNotFoundException extends \DomainException
{
    public static function withUuid(string $uuid): self
    {
        return new self("Scheduled report with UUID {$uuid} not found.");
    }
}
