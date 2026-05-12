<?php

declare(strict_types=1);

namespace App\Cash\Domain\Exception;

final class CashSessionCannotStartClosingException extends \DomainException
{
    public function __construct()
    {
        parent::__construct('Only open cash sessions can start closing.');
    }
}
