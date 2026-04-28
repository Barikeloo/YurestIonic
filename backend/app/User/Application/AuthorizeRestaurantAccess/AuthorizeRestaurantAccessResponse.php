<?php

namespace App\User\Application\AuthorizeRestaurantAccess;

class AuthorizeRestaurantAccessResponse
{
    public const AUTHORIZED = 'authorized';

    public const NOT_AUTHENTICATED = 'not_authenticated';

    public const RESTAURANT_NOT_FOUND = 'restaurant_not_found';

    public const FORBIDDEN = 'forbidden';

    private function __construct(
        private string $status,
    ) {}

    public static function authorized(): self
    {
        return new self(self::AUTHORIZED);
    }

    public static function notAuthenticated(): self
    {
        return new self(self::NOT_AUTHENTICATED);
    }

    public static function restaurantNotFound(): self
    {
        return new self(self::RESTAURANT_NOT_FOUND);
    }

    public static function forbidden(): self
    {
        return new self(self::FORBIDDEN);
    }

    public function status(): string
    {
        return $this->status;
    }
}
