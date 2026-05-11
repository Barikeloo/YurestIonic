<?php

namespace App\SuperAdmin\Infrastructure\Entrypoint\Http;

use App\SuperAdmin\Application\AuthenticateSuperAdmin\AuthenticateSuperAdmin;
use App\SuperAdmin\Domain\Exception\InvalidSuperAdminCredentialsException;
use App\SuperAdmin\Infrastructure\Entrypoint\Http\Requests\LoginRequest;
use Illuminate\Http\JsonResponse;

final class LoginController
{
    public function __construct(
        private AuthenticateSuperAdmin $authenticateSuperAdmin,
    ) {}

    public function __invoke(LoginRequest $request): JsonResponse
    {
        try {
            $response = ($this->authenticateSuperAdmin)($request->toCommand());
        } catch (InvalidSuperAdminCredentialsException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 401);
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        $request->session()->regenerate();
        $request->session()->put('super_admin_id', $response->id);
        $request->session()->forget('auth_user_id');

        return new JsonResponse($response->toArray());
    }
}
