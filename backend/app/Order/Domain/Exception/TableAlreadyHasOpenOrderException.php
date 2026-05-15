<?php

namespace App\Order\Domain\Exception;

final class TableAlreadyHasOpenOrderException extends \DomainException
{
    public static function create(): self
    {
        return new self('La mesa ya tiene una comanda abierta.');
    }
}
