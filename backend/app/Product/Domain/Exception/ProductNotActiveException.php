<?php

namespace App\Product\Domain\Exception;

final class ProductNotActiveException extends \DomainException
{
    public static function withId(string $id): self
    {
        return new self("Product with ID {$id} is not active.");
    }
}
