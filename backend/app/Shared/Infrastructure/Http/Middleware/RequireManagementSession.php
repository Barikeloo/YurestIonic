<?php

namespace App\Shared\Infrastructure\Http\Middleware;

use App\SuperAdmin\Infrastructure\Persistence\Models\EloquentSuperAdmin;
use App\User\Infrastructure\Persistence\Models\EloquentUser;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RequireManagementSession
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->hasSession()) {
            return new JsonResponse([
                'message' => 'Session is required for management routes.',
            ], 500);
        }

        $superAdminUuid = $request->session()->get('super_admin_id');

        if (is_string($superAdminUuid) && $superAdminUuid !== '') {
            $superAdmin = EloquentSuperAdmin::query()->where('uuid', $superAdminUuid)->first();

            if ($superAdmin !== null) {
                return $next($request);
            }

            $request->session()->forget('super_admin_id');
        }

        $authUserUuid = $request->session()->get('auth_user_id');

        if (! is_string($authUserUuid) || $authUserUuid === '') {
            return new JsonResponse([
                'message' => 'Not authenticated.',
            ], 401);
        }

        $user = EloquentUser::query()->where('uuid', $authUserUuid)->first();

        if ($user === null) {
            $request->session()->forget('auth_user_id');

            return new JsonResponse([
                'message' => 'Not authenticated.',
            ], 401);
        }

        if ($user->role !== 'admin') {
            return new JsonResponse([
                'message' => 'Forbidden.',
            ], 403);
        }

        return $next($request);
    }
}
