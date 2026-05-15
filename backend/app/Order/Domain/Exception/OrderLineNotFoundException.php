<?php

namespace App\Order\Domain\Exception;

final class OrderLineNotFoundException extends \DomainException
{
    public static function withId(string $id): self
    {
        return new self("Order line with id {$id} not found.");
    }
}
