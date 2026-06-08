<?php

namespace App\Product\Domain\Exception;

final class ProductPhotoUploadTokenExpiredException extends \DomainException
{
    public static function withToken(string $token): self
    {
        return new self('Photo upload token has expired.');
    }
}
