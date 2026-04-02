<?php

namespace App\SuperAdmin\Infrastructure\Entrypoint\Http;

use App\SuperAdmin\Application\AuthenticateSuperAdmin\AuthenticateSuperAdmin;
use App\SuperAdmin\Application\AuthenticateSuperAdmin\AuthenticateSuperAdminResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class LoginController
{
    public function __construct(
        private AuthenticateSuperAdmin $authenticateSuperAdmin,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $response = $this->authenticateSuperAdmin->__invoke(
            $validated['email'],
            $validated['password'],
        );

        if ($response->status() === AuthenticateSuperAdminResponse::INVALID_CREDENTIALS) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Invalid credentials.',
            ], 401);
        }

        $request->session()->regenerate();
        $request->session()->put('super_admin_id', $response->id());
        $request->session()->forget('auth_user_id');

        return new JsonResponse([
            'success' => true,
            'id' => $response->id(),
            'name' => $response->name(),
            'email' => $response->email(),
        ]);
    }
}
