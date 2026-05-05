<?php

namespace App\User\Application\CreateUser;

final readonly class CreateUserCommand
{
    public function __construct(
        public string $name,
        public string $email,
        public string $plainPassword,
    ) {}
}
