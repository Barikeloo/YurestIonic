<?php

declare(strict_types=1);

namespace App\Cash\Domain\Exception;

final class CashSessionNotOpenForMovementException extends \DomainException
{
    public function __construct()
    {
        parent::__construct('Cannot register movements on a session that is not open.');
    }
}
