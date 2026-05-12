<?php

declare(strict_types=1);

namespace App\Sale\Domain\Exception;

final class PaymentAmountMustBePositiveException extends \DomainException
{
    public static function create(): self
    {
        return new self('Payment amount must be greater than 0.');
    }
}
