<?php

namespace App\Restaurant\Application\SelectRestaurantContext;

class SelectRestaurantContextResponse
{
    public const SUCCESS = 'success';
    public const NOT_AUTHENTICATED = 'not_authenticated';
    public const RESTAURANT_NOT_FOUND = 'restaurant_not_found';
    public const LINKED_RESTAURANT_NOT_FOUND = 'linked_restaurant_not_found';
    public const LINKED_RESTAURANT_WITHOUT_TAX_ID = 'linked_restaurant_without_tax_id';
    public const FORBIDDEN = 'forbidden';

    private function __construct(
        private string $status,
        private ?string $restaurantUuid,
        private ?string $restaurantName,
    ) {}

    public static function success(string $restaurantUuid, string $restaurantName): self
    {
        return new self(self::SUCCESS, $restaurantUuid, $restaurantName);
    }

    public static function notAuthenticated(): self
    {
        return new self(self::NOT_AUTHENTICATED, null, null);
    }

    public static function restaurantNotFound(): self
    {
        return new self(self::RESTAURANT_NOT_FOUND, null, null);
    }

    public static function linkedRestaurantNotFound(): self
    {
        return new self(self::LINKED_RESTAURANT_NOT_FOUND, null, null);
    }

    public static function linkedRestaurantWithoutTaxId(): self
    {
        return new self(self::LINKED_RESTAURANT_WITHOUT_TAX_ID, null, null);
    }

    public static function forbidden(): self
    {
        return new self(self::FORBIDDEN, null, null);
    }

    public function status(): string
    {
        return $this->status;
    }

    public function restaurantUuid(): ?string
    {
        return $this->restaurantUuid;
    }

    public function restaurantName(): ?string
    {
        return $this->restaurantName;
    }
}