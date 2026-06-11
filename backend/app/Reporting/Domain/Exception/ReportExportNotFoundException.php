<?php

declare(strict_types=1);

namespace App\Reporting\Domain\Exception;

final class ReportExportNotFoundException extends \DomainException
{
    public static function withUuid(string $uuid): self
    {
        return new self("Report export {$uuid} not found.");
    }
}
