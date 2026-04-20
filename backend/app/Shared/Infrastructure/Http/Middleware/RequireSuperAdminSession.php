<?php

namespace App\Shared\Infrastructure\Http\Middleware;

use App\Shared\Domain\ValueObject\Uuid;
use App\SuperAdmin\Domain\Interfaces\SuperAdminRepositoryInterface;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RequireSuperAdminSession
{
    public function __construct(
        private SuperAdminRepositoryInterface $superAdminRepository,
    ) {}

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

        $superAdmin = $this->superAdminRepository->findById(Uuid::create($superAdminUuid));

        if ($superAdmin === null) {
            $request->session()->forget('super_admin_id');

            return new JsonResponse([
                'message' => 'Not authenticated as superadmin.',
            ], 401);
        }

        return $next($request);
    }
}
