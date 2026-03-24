<?php

namespace App\User\Infrastructure\Entrypoint\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LogoutController
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->session()->forget('auth_user_id');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return new JsonResponse([
            'success' => true,
            'message' => 'Logged out.',
        ]);
    }
}
