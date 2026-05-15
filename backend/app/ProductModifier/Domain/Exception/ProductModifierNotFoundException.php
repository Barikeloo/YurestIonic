<?php

namespace App\ProductModifier\Domain\Exception;

final class ProductModifierNotFoundException extends \DomainException
{
    public static function withId(string $id): self
    {
        return new self("Product modifier with id {$id} not found.");
    }
}
