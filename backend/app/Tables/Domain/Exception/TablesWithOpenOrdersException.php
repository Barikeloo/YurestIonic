<?php

namespace App\Tables\Domain\Exception;

use RuntimeException;

final class TablesWithOpenOrdersException extends RuntimeException
{
    public static function create(): self
    {
        return new self('No se pueden fusionar o separar mesas con órdenes marcadas para cobrar.');
    }
}
