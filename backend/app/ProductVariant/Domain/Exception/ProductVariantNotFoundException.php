<?php

namespace App\ProductVariant\Domain\Exception;

final class ProductVariantNotFoundException extends \DomainException
{
    public static function withId(string $id): self
    {
        return new self("Product variant with id {$id} not found.");
    }
}
