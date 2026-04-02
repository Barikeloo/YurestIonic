<?php

namespace App\Restaurant\Application\AuthorizeRestaurantUpdate;

final class AuthorizeRestaurantUpdateResponse
{
    public const SUCCESS = 'success';
    public const NOT_AUTHENTICATED = 'not_authenticated';
    public const FORBIDDEN = 'forbidden';
    public const RESTAURANT_NOT_FOUND = 'restaurant_not_found';
    public const LINKED_RESTAURANT_WITHOUT_TAX_ID = 'linked_restaurant_without_tax_id';

    private function __construct(
        private string $status,
    ) {}

    public static function success(): self
    {
        return new self(self::SUCCESS);
    }

    public static function notAuthenticated(): self
    {
        return new self(self::NOT_AUTHENTICATED);
    }

    public static function forbidden(): self
    {
        return new self(self::FORBIDDEN);
    }

    public static function restaurantNotFound(): self
    {
        return new self(self::RESTAURANT_NOT_FOUND);
    }

    public static function linkedRestaurantWithoutTaxId(): self
    {
        return new self(self::LINKED_RESTAURANT_WITHOUT_TAX_ID);
    }

    public function status(): string
    {
        return $this->status;
    }
}
