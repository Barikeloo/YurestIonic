<?php

declare(strict_types=1);

namespace App\Reporting\Domain\Exception;

final class InvalidScheduleException extends \DomainException
{
    public static function because(string $reason): self
    {
        return new self("Invalid schedule: {$reason}.");
    }
}
