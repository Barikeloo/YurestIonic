<?php

namespace App\User\Infrastructure\Entrypoint\Http;

use App\User\Infrastructure\Entrypoint\Http\Requests\LogoutRequest;
use Illuminate\Http\JsonResponse;

final class LogoutController
{
    public function __invoke(LogoutRequest $request): JsonResponse
    {
        $request->session()->forget('auth_user_id');
        $request->session()->forget('tenant_restaurant_uuid');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return new JsonResponse([
            'success' => true,
            'message' => 'Logged out.',
        ]);
    }
}
