<?php

namespace App\Product\Domain\Exception;

final class ProductPhotoUploadTokenNotFoundException extends \DomainException
{
    public static function withToken(string $token): self
    {
        return new self('Photo upload token not found.');
    }
}
