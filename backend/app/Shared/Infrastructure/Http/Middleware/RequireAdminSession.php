<?php

namespace App\Shared\Infrastructure\Http\Middleware;

use App\User\Infrastructure\Persistence\Models\EloquentUser;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RequireAdminSession
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->hasSession()) {
            return new JsonResponse([
                'message' => 'Session is required for admin routes.',
            ], 500);
        }

        $authUserUuid = $request->session()->get('auth_user_id');

        if (! is_string($authUserUuid) || $authUserUuid === '') {
            return new JsonResponse([
                'message' => 'Not authenticated.',
            ], 401);
        }

        $user = EloquentUser::query()->where('uuid', $authUserUuid)->first();

        if ($user === null) {
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
