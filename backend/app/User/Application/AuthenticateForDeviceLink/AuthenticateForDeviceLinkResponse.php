<?php

namespace App\User\Application\AuthenticateForDeviceLink;

final readonly class AuthenticateForDeviceLinkResponse
{
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
        public ?string $restaurantId,
        public ?string $restaurantName,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'restaurantId' => $this->restaurantId,
            'restaurantName' => $this->restaurantName,
        ];
    }
}
