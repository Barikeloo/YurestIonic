<?php

declare(strict_types=1);

namespace App\GuestOrder\Application\RegisterCustomerAccount;

final readonly class RegisterCustomerAccountCommand
{
    public function __construct(
        public string $token,
        public string $name,
        public string $email,
        public string $password,
    ) {}
}
