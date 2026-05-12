<?php

declare(strict_types=1);

namespace App\Cash\Domain\Exception;

final class CashSessionNotFoundException extends \DomainException
{
    public static function withId(string $id): self
    {
        return new self("Cash session with id {$id} not found.");
    }
}
