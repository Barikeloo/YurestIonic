<?php

namespace App\SuperAdmin\Infrastructure\Entrypoint\Http;

use App\SuperAdmin\Infrastructure\Entrypoint\Http\Requests\LogoutRequest;
use Illuminate\Http\JsonResponse;

final class LogoutController
{
    public function __invoke(LogoutRequest $request): JsonResponse
    {
        $request->session()->forget('super_admin_id');
        $request->session()->forget('tenant_restaurant_uuid');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return new JsonResponse(['message' => 'Logged out.']);
    }
}
