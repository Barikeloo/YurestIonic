<?php

namespace App\Shared\Infrastructure\Http\Middleware;

use App\Shared\Domain\ValueObject\Uuid;
use App\SuperAdmin\Domain\Interfaces\SuperAdminRepositoryInterface;
use App\User\Domain\Interfaces\UserRepositoryInterface;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RequireManagementSession
{
    public function __construct(
        private SuperAdminRepositoryInterface $superAdminRepository,
        private UserRepositoryInterface $userRepository,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->hasSession()) {
            return new JsonResponse([
                'message' => 'Session is required for management routes.',
            ], 500);
        }

        $superAdminUuid = $request->session()->get('super_admin_id');

        if (is_string($superAdminUuid) && $superAdminUuid !== '') {
            $superAdmin = $this->superAdminRepository->findById(Uuid::create($superAdminUuid));

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

        $user = $this->userRepository->findById($authUserUuid);

        if ($user === null) {
            $request->session()->forget('auth_user_id');

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
