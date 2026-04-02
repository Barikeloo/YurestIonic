<?php

namespace App\Restaurant\Application\ValidateRestaurantCompanyMode;

final class ValidateRestaurantCompanyModeResponse
{
    public const SUCCESS = 'success';
    public const TAX_ID_ALREADY_EXISTS = 'tax_id_already_exists';
    public const TAX_ID_DOES_NOT_EXIST = 'tax_id_does_not_exist';

    private function __construct(
        private string $status,
    ) {}

    public static function success(): self
    {
        return new self(self::SUCCESS);
    }

    public static function taxIdAlreadyExists(): self
    {
        return new self(self::TAX_ID_ALREADY_EXISTS);
    }

    public static function taxIdDoesNotExist(): self
    {
        return new self(self::TAX_ID_DOES_NOT_EXIST);
    }

    public function status(): string
    {
        return $this->status;
    }
}
