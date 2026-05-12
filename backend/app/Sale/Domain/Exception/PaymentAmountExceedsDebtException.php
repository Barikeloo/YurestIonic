<?php

declare(strict_types=1);

namespace App\Sale\Domain\Exception;

final class PaymentAmountExceedsDebtException extends \DomainException
{
    public static function create(int $amount, int $remaining): self
    {
        return new self("Payment amount ({$amount}) exceeds remaining debt ({$remaining}).");
    }
}
