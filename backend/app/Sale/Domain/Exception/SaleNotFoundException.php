<?php

declare(strict_types=1);

namespace App\Sale\Domain\Exception;

final class SaleNotFoundException extends \DomainException
{
    public static function withId(string $id): self
    {
        return new self("Sale with id {$id} not found.");
    }
}
