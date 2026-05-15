<?php

namespace App\Order\Domain\Exception;

final class OrderIsNotOpenException extends \DomainException
{
    public static function create(): self
    {
        return new self('Cannot add lines to an order that is not open.');
    }
}
