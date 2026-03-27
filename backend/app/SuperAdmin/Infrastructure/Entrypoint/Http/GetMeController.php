<?php

namespace App\SuperAdmin\Infrastructure\Entrypoint\Http;

use App\SuperAdmin\Infrastructure\Persistence\Models\EloquentSuperAdmin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class GetMeController
{
    public function __invoke(Request $request): JsonResponse
    {
        $superAdminUuid = $request->session()->get('super_admin_id');

        if (! is_string($superAdminUuid) || $superAdminUuid === '') {
            return new JsonResponse([
                'success' => false,
                'message' => 'Not authenticated as superadmin.',
            ], 401);
        }

        $superAdmin = EloquentSuperAdmin::query()->where('uuid', $superAdminUuid)->first();

        if ($superAdmin === null) {
            $request->session()->forget('super_admin_id');

            return new JsonResponse([
                'success' => false,
                'message' => 'Not authenticated as superadmin.',
            ], 401);
        }

        return new JsonResponse([
            'success' => true,
            'id' => $superAdmin->uuid,
            'name' => $superAdmin->name,
            'email' => $superAdmin->email,
        ]);
    }
}
