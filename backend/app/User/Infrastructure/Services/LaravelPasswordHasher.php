<?php

namespace App\User\Infrastructure\Services;

use App\User\Domain\Interfaces\PasswordHasherInterface;
use Illuminate\Support\Facades\Hash;

class LaravelPasswordHasher implements PasswordHasherInterface
{
    public function hash(string $plainPassword): string
    {
        return Hash::make($plainPassword);
    }

    public function verify(string $plainPassword, string $passwordHash): bool
    {
        return Hash::check($plainPassword, $passwordHash);
    }
}
