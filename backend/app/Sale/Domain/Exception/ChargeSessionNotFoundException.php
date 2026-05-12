<?php

declare(strict_types=1);

namespace App\Sale\Domain\Exception;

final class ChargeSessionNotFoundException extends \DomainException
{
    public static function withId(string $id): self
    {
        return new self("Charge session with id {$id} not found.");
    }
}
