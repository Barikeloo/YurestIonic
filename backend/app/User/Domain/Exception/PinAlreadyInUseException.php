<?php

namespace App\User\Domain\Exception;

final class PinAlreadyInUseException extends \DomainException
{
    public function __construct()
    {
        parent::__construct('Este PIN ya está en uso en este restaurante.');
    }
}
