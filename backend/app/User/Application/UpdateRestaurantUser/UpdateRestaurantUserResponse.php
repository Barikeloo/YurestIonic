<?php

namespace App\User\Application\UpdateRestaurantUser;

class UpdateRestaurantUserResponse
{
    private function __construct(
        private bool $found,
        private string $uuid,
    ) {}

    public static function success(string $uuid): self
    {
        return new self(true, $uuid);
    }

    public static function notFound(): self
    {
        return new self(false, '');
    }

    public function found(): bool
    {
        return $this->found;
    }

    public function uuid(): string
    {
        return $this->uuid;
    }

    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'found' => $this->found,
        ];
    }
}
