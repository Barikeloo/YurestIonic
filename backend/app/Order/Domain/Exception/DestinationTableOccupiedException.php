<?php

namespace App\Order\Domain\Exception;

final class DestinationTableOccupiedException extends \DomainException
{
    public static function create(): self
    {
        return new self('La mesa destino ya tiene una comanda activa.');
    }
}
