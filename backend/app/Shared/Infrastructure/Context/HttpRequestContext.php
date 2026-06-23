<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Context;

use App\Shared\Application\Context\RequestContextInterface;
use App\Shared\Infrastructure\Tenant\TenantContext;

final readonly class HttpRequestContext implements RequestContextInterface
{
    public function __construct(
        private TenantContext $tenant,
    ) {}

    public function restaurantId(): ?string
    {
        return $this->tenant->restaurantUuid();
    }

    public function userId(): ?string
    {
        $request = request();

        if (! $request->hasSession()) {
            return null;
        }

        $userId = $request->session()->get('auth_user_id');

        return is_string($userId) && $userId !== '' ? $userId : null;
    }

    public function ipAddress(): ?string
    {
        return request()->ip();
    }

    public function deviceId(): ?string
    {
        $request = request();

        $deviceId = $request->input('device_id');
        if (! is_string($deviceId) || $deviceId === '') {
            $deviceId = $request->header('X-Device-Id');
        }

        return is_string($deviceId) && $deviceId !== '' ? $deviceId : null;
    }

    public function sessionId(): ?string
    {
        return null;
    }
}
