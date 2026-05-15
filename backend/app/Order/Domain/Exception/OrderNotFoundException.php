<?php

namespace App\Order\Domain\Exception;

final class OrderNotFoundException extends \DomainException
{
    public static function withId(string $id): self
    {
        return new self("Order with id {$id} not found.");
    }
}
