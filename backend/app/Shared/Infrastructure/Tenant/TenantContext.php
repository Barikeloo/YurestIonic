<?php

namespace App\Shared\Infrastructure\Tenant;

use RuntimeException;

final class TenantContext
{
    private ?int $restaurantId = null;

    private ?string $restaurantUuid = null;

    private bool $admin = false;

    public function set(?int $restaurantId, ?string $restaurantUuid, bool $admin): void
    {
        $this->restaurantId = $restaurantId;
        $this->restaurantUuid = $restaurantUuid;
        $this->admin = $admin;
    }

    public function clear(): void
    {
        $this->restaurantId = null;
        $this->restaurantUuid = null;
        $this->admin = false;
    }

    public function restaurantId(): ?int
    {
        return $this->restaurantId;
    }

    public function restaurantUuid(): ?string
    {
        return $this->restaurantUuid;
    }

    public function isAdmin(): bool
    {
        return $this->admin;
    }

    public function requireRestaurantId(): int
    {
        if ($this->restaurantId === null) {
            throw new RuntimeException('Tenant restaurant context is required.');
        }

        return $this->restaurantId;
    }
}
