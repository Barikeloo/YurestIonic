<?php

namespace App\Restaurant\Application\GetAdminRestaurantCollection;

class GetAdminRestaurantCollectionResponse
{
    private function __construct(
        private array $data,
    ) {}

    public static function create(array $data): self
    {
        return new self($data);
    }

    public function data(): array
    {
        return $this->data;
    }
}
