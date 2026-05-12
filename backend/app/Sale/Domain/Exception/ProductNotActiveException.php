<?php

declare(strict_types=1);

namespace App\Sale\Domain\Exception;

final class ProductNotActiveException extends \DomainException
{
    public static function create(): self
    {
        return new self('Only active products can be sold.');
    }
}
