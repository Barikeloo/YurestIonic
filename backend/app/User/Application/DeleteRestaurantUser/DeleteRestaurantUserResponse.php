<?php

namespace App\User\Application\DeleteRestaurantUser;

class DeleteRestaurantUserResponse
{
    private function __construct(
        private bool $found,
    ) {}

    public static function success(): self
    {
        return new self(true);
    }

    public static function notFound(): self
    {
        return new self(false);
    }

    public function found(): bool
    {
        return $this->found;
    }

    public function toArray(): array
    {
        return [
            'success' => $this->found,
        ];
    }
}
