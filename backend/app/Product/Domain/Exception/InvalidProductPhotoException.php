<?php

namespace App\Product\Domain\Exception;

final class InvalidProductPhotoException extends \DomainException
{
    public static function unreadable(): self
    {
        return new self('The uploaded photo could not be read or processed.');
    }
}
