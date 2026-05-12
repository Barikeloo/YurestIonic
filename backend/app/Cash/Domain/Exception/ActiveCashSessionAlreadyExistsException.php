<?php

declare(strict_types=1);

namespace App\Cash\Domain\Exception;

final class ActiveCashSessionAlreadyExistsException extends \DomainException
{
    public static function forDevice(string $deviceId): self
    {
        return new self("An active cash session already exists for device {$deviceId}.");
    }
}
