<?php

declare(strict_types=1);

namespace App\Cash\Domain\Exception;

final class CashSessionCannotCloseException extends \DomainException
{
    public function __construct()
    {
        parent::__construct('Only cash sessions in closing state can be closed.');
    }
}
