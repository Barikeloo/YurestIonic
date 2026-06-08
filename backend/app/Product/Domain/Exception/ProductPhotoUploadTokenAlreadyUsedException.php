<?php

namespace App\Product\Domain\Exception;

final class ProductPhotoUploadTokenAlreadyUsedException extends \DomainException
{
    public static function withToken(string $token): self
    {
        return new self('Photo upload token has already been used.');
    }
}
