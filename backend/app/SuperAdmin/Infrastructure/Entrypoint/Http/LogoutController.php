<?php

namespace App\SuperAdmin\Infrastructure\Entrypoint\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class LogoutController
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->session()->forget('super_admin_id');
        $request->session()->forget('tenant_restaurant_uuid');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return new JsonResponse([
            'success' => true,
            'message' => 'Logged out.',
        ]);
    }
}
