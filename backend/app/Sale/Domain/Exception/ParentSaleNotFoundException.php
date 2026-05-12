<?php

declare(strict_types=1);

namespace App\Sale\Domain\Exception;

final class ParentSaleNotFoundException extends \DomainException
{
    public static function withId(string $id): self
    {
        return new self("Parent sale with id {$id} not found.");
    }
}
