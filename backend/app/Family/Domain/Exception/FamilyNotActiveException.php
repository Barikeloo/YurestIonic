<?php

namespace App\Family\Domain\Exception;

final class FamilyNotActiveException extends \DomainException
{
    public static function withId(string $id): self
    {
        return new self("Family with id {$id} is not active.");
    }
}
