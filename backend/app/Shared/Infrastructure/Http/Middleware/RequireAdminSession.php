<?php

namespace App\Shared\Infrastructure\Http\Middleware;

use App\User\Domain\Interfaces\UserRepositoryInterface;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RequireAdminSession
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {}

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

        $user = $this->userRepository->findById($authUserUuid);

        if ($user === null) {
            return new JsonResponse([
                'message' => 'Not authenticated.',
            ], 401);
        }

        if ($user->role() === null || ! $user->role()->isAdmin()) {
            return new JsonResponse([
                'message' => 'Forbidden.',
            ], 403);
        }

        return $next($request);
    }
}
