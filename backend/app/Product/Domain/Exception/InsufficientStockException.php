<?php

namespace App\Product\Domain\Exception;

final class InsufficientStockException extends \DomainException
{
    public static function forProduct(string $productId, int $available, int $requested): self
    {
        return new self(
            "Insufficient stock for product {$productId}. Available: {$available}, requested: {$requested}."
        );
    }
}
