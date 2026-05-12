<?php

declare(strict_types=1);

namespace App\Cash\Domain\Exception;

final class CashSessionCannotCancelClosingException extends \DomainException
{
    public function __construct()
    {
        parent::__construct('Only closing cash sessions can cancel closing.');
    }
}
