<?php

declare(strict_types=1);

namespace App\Sale\Domain\Exception;

final class OrderLineNotFoundException extends \DomainException
{
    public static function withId(string $id): self
    {
        return new self("Order line with id {$id} not found.");
    }
}
