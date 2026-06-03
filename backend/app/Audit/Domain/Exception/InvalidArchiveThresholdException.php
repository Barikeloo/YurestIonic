<?php

declare(strict_types=1);

namespace App\Audit\Domain\Exception;

final class InvalidArchiveThresholdException extends \DomainException
{
    public static function nonPositiveDays(int $days): self
    {
        return new self("Archive threshold must be at least 1 day, got {$days}.");
    }

    public static function thresholdInFuture(\DateTimeImmutable $threshold): self
    {
        return new self('Archive threshold must not be in the future, got '.$threshold->format(\DateTimeInterface::ATOM).'.');
    }
}
