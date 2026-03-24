<?php

namespace App\User\Domain\Interfaces;

interface PasswordHasherInterface
{
    public function hash(string $plainPassword): string;

    public function verify(string $plainPassword, string $passwordHash): bool;
}
