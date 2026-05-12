<?php

declare(strict_types=1);

namespace App\Cash\Domain\Exception;

final class PendingSalesPreventClosingException extends \DomainException
{
    public function __construct()
    {
        parent::__construct('Cannot close cash session while there are pending sales.');
    }
}
