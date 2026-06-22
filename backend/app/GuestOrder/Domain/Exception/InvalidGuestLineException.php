<?php

declare(strict_types=1);

namespace App\GuestOrder\Domain\Exception;

final class InvalidGuestLineException extends \DomainException
{
    public static function productNotAvailable(string $productId): self
    {
        return new self("Product '{$productId}' is not available.");
    }

    public static function productNotFound(string $productId): self
    {
        return new self("Product '{$productId}' not found.");
    }

    public static function menuNotFound(string $menuId): self
    {
        return new self("Menu '{$menuId}' not found or not active.");
    }

    public static function lineNotFound(string $lineId): self
    {
        return new self("Line '{$lineId}' not found or does not belong to this session.");
    }

    public static function lineAlreadySent(string $lineId): self
    {
        return new self("Line '{$lineId}' was already sent to the kitchen.");
    }
}
