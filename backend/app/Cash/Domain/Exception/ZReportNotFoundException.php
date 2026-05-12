<?php

declare(strict_types=1);

namespace App\Cash\Domain\Exception;

final class ZReportNotFoundException extends \DomainException
{
    public static function withId(string $id): self
    {
        return new self("Z-Report with id {$id} not found.");
    }
}
