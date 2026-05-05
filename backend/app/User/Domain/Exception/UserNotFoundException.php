<?php

namespace App\User\Domain\Exception;

final class UserNotFoundException extends \DomainException
{
    public static function withEmail(string $email): self
    {
        return new self("User with email {$email} not found.");
    }

    public static function withId(string $userId): self
    {
        return new self("User with id {$userId} not found.");
    }
}
