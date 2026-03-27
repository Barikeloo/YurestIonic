<?php

namespace App\Shared\Infrastructure\Http\Middleware;

use App\SuperAdmin\Infrastructure\Persistence\Models\EloquentSuperAdmin;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RequireSuperAdminSession
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->hasSession()) {
            return new JsonResponse([
                'message' => 'Session is required for superadmin routes.',
            ], 500);
        }

        $superAdminUuid = $request->session()->get('super_admin_id');

        if (! is_string($superAdminUuid) || $superAdminUuid === '') {
            return new JsonResponse([
                'message' => 'Not authenticated as superadmin.',
            ], 401);
        }

        $superAdmin = EloquentSuperAdmin::query()->where('uuid', $superAdminUuid)->first();

        if ($superAdmin === null) {
            $request->session()->forget('super_admin_id');

            return new JsonResponse([
                'message' => 'Not authenticated as superadmin.',
            ], 401);
        }

        return $next($request);
    }
}
