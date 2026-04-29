<?php

declare(strict_types=1);

namespace App\Sale\Domain\Exception;

final class OrderHasPartialPaymentsException extends \DomainException
{
    public function __construct(public readonly int $paidCents)
    {
        parent::__construct(sprintf(
            'La cuenta ya tiene %.2f € cobrados. Cancela la venta previa para poder dividir el resto a partes iguales.',
            $paidCents / 100
        ));
    }
}
