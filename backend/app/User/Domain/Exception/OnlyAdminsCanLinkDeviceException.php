<?php

namespace App\User\Domain\Exception;

final class OnlyAdminsCanLinkDeviceException extends \DomainException
{
    public function __construct()
    {
        parent::__construct('Only admin users can link devices.');
    }
}
