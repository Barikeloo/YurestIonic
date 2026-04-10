<?php

namespace App\User\Application\AuthenticateForDeviceLink;

final readonly class AuthenticateForDeviceLinkResponse
{
    private function __construct(
        public bool $success,
        public int $statusCode,
        public ?string $message,
        public ?string $id,
        public ?string $name,
        public ?string $email,
        public ?string $restaurant_id = null,
        public ?string $restaurant_name = null,
    ) {}

    public static function authenticated(
        string $id,
        string $name,
        string $email,
        ?string $restaurantId = null,
        ?string $restaurantName = null,
    ): self {
        return new self(
            success: true,
            statusCode: 200,
            message: null,
            id: $id,
            name: $name,
            email: $email,
            restaurant_id: $restaurantId,
            restaurant_name: $restaurantName,
        );
    }

    public static function notFound(): self
    {
        return new self(
            success: false,
            statusCode: 404,
            message: 'User not registered.',
            id: null,
            name: null,
            email: null,
        );
    }

    public static function invalidCredentials(): self
    {
        return new self(
            success: false,
            statusCode: 401,
            message: 'Invalid credentials.',
            id: null,
            name: null,
            email: null,
        );
    }

    public static function forbidden(): self
    {
        return new self(
            success: false,
            statusCode: 403,
            message: 'Only admin users can link devices.',
            id: null,
            name: null,
            email: null,
        );
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'restaurant_id' => $this->restaurant_id,
            'restaurant_name' => $this->restaurant_name,
        ];
    }
}
