<?php

namespace App\Order\Domain\Exception;

final class SameTableTransferException extends \DomainException
{
    public static function create(): self
    {
        return new self('La mesa de origen y la de destino no pueden ser la misma.');
    }
}
