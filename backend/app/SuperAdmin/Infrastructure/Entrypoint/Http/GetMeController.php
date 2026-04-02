<?php

namespace App\SuperAdmin\Infrastructure\Entrypoint\Http;

use App\SuperAdmin\Application\GetSuperAdminMe\GetSuperAdminMe;
use App\SuperAdmin\Application\GetSuperAdminMe\GetSuperAdminMeResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class GetMeController
{
    public function __construct(
        private GetSuperAdminMe $getSuperAdminMe,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $superAdminUuid = $request->session()->get('super_admin_id');
        $response = $this->getSuperAdminMe->__invoke(is_string($superAdminUuid) ? $superAdminUuid : null);

        if ($response->status() === GetSuperAdminMeResponse::NOT_AUTHENTICATED) {
            $request->session()->forget('super_admin_id');

            return new JsonResponse([
                'success' => false,
                'message' => 'Not authenticated as superadmin.',
            ], 401);
        }

        return new JsonResponse([
            'success' => true,
            'id' => $response->id(),
            'name' => $response->name(),
            'email' => $response->email(),
        ]);
    }
}
