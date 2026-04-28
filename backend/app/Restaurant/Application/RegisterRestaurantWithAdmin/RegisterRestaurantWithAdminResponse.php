<?php

namespace App\Restaurant\Application\RegisterRestaurantWithAdmin;

final readonly class RegisterRestaurantWithAdminResponse
{
    private function __construct(
        public string $restaurantId,
        public string $restaurantName,
        public string $adminEmail,
        public string $adminName,
        public string $adminPin,
    ) {}

    public static function create(
        string $restaurantId,
        string $restaurantName,
        string $adminEmail,
        string $adminName,
        string $adminPin,
    ): self {
        return new self(
            restaurantId: $restaurantId,
            restaurantName: $restaurantName,
            adminEmail: $adminEmail,
            adminName: $adminName,
            adminPin: $adminPin,
        );
    }

    public function toArray(): array
    {
        return [
            'restaurant_id' => $this->restaurantId,
            'restaurant_name' => $this->restaurantName,
            'admin_email' => $this->adminEmail,
            'admin_name' => $this->adminName,
            'admin_pin' => $this->adminPin,
            'message' => 'Restaurant and admin user created successfully.',
        ];
    }
}
