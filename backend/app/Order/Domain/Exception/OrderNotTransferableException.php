<?php

namespace App\Order\Domain\Exception;

final class OrderNotTransferableException extends \DomainException
{
    public static function create(): self
    {
        return new self('Solo las comandas abiertas o por cobrar pueden traspasarse.');
    }
}
