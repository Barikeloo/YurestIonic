<?php

declare(strict_types=1);

namespace App\Cash\Domain\Exception;

final class ZReportAlreadyExistsException extends \DomainException
{
    public static function create(): self
    {
        return new self('A Z-Report already exists for this cash session.');
    }
}
