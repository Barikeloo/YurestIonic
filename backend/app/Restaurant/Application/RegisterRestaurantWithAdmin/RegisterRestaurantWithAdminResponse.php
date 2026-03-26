<?php

namespace App\Restaurant\Application\RegisterRestaurantWithAdmin;

final readonly class RegisterRestaurantWithAdminResponse
{
    private function __construct(
        public string $restaurantId,
        public string $restaurantName,
        public string $adminEmail,
        public string $adminName,
    ) {}

    public static function create(
        string $restaurantId,
        string $restaurantName,
        string $adminEmail,
        string $adminName,
    ): self {
        return new self(
            restaurantId: $restaurantId,
            restaurantName: $restaurantName,
            adminEmail: $adminEmail,
            adminName: $adminName,
        );
    }

    public function toArray(): array
    {
        return [
            'restaurant_id' => $this->restaurantId,
            'restaurant_name' => $this->restaurantName,
            'admin_email' => $this->adminEmail,
            'admin_name' => $this->adminName,
            'message' => 'Restaurant and admin user created successfully.',
        ];
    }
}