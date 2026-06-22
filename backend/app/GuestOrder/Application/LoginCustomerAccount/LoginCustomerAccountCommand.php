<?php

declare(strict_types=1);

namespace App\GuestOrder\Application\LoginCustomerAccount;

final readonly class LoginCustomerAccountCommand
{
    public function __construct(
        public string $token,
        public string $email,
        public string $password,
    ) {}
}
