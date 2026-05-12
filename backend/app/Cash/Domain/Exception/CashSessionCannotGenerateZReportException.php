<?php

declare(strict_types=1);

namespace App\Cash\Domain\Exception;

final class CashSessionCannotGenerateZReportException extends \DomainException
{
    public static function withStatus(string $status): self
    {
        return new self("Cannot generate Z-Report on a session with status {$status}.");
    }

    public static function finalAmountRequired(): self
    {
        return new self('Final amount is required to generate the Z-Report.');
    }
}
