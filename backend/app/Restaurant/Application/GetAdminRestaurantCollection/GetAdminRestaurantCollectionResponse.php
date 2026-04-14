<?php

namespace App\Restaurant\Application\GetAdminRestaurantCollection;

class GetAdminRestaurantCollectionResponse
{
    public const SUCCESS = 'success';
    public const NOT_AUTHENTICATED = 'not_authenticated';
    public const LINKED_RESTAURANT_NOT_FOUND = 'linked_restaurant_not_found';
    public const LINKED_RESTAURANT_WITHOUT_TAX_ID = 'linked_restaurant_without_tax_id';

    /**
     * @param array<array{uuid: string, name: string, legal_name: string|null, tax_id: string|null, email: string, users: int, zones: int, products: int}> $data
     */
    private function __construct(
        private string $status,
        private array $data,
    ) {}

    /**
     * @param array<array{uuid: string, name: string, legal_name: string|null, tax_id: string|null, email: string, users: int, zones: int, products: int}> $data
     */
    public static function success(array $data): self
    {
        return new self(self::SUCCESS, $data);
    }

    public static function notAuthenticated(): self
    {
        return new self(self::NOT_AUTHENTICATED, []);
    }

    public static function linkedRestaurantNotFound(): self
    {
        return new self(self::LINKED_RESTAURANT_NOT_FOUND, []);
    }

    public static function linkedRestaurantWithoutTaxId(): self
    {
        return new self(self::LINKED_RESTAURANT_WITHOUT_TAX_ID, []);
    }

    public function status(): string
    {
        return $this->status;
    }

    /**
     * @return array<array{uuid: string, name: string, legal_name: string|null, tax_id: string|null, email: string, users: int, zones: int, products: int}>
     */
    public function data(): array
    {
        return $this->data;
    }
}