<?php

namespace App\User\Domain\Exception;

final class CannotDemoteSelfAdminException extends \DomainException
{
    public function __construct()
    {
        parent::__construct('No puedes cambiar tu propio rol de administrador.');
    }
}
