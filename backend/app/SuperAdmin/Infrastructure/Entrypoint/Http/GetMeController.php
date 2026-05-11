<?php

namespace App\SuperAdmin\Infrastructure\Entrypoint\Http;

use App\SuperAdmin\Application\GetSuperAdminMe\GetSuperAdminMe;
use App\SuperAdmin\Domain\Exception\SuperAdminNotAuthenticatedException;
use App\SuperAdmin\Infrastructure\Entrypoint\Http\Requests\GetMeRequest;
use Illuminate\Http\JsonResponse;

final class GetMeController
{
    public function __construct(
        private GetSuperAdminMe $getSuperAdminMe,
    ) {}

    public function __invoke(GetMeRequest $request): JsonResponse
    {
        try {
            $response = ($this->getSuperAdminMe)($request->toCommand());
        } catch (SuperAdminNotAuthenticatedException $e) {
            $request->session()->forget('super_admin_id');

            return new JsonResponse(['message' => $e->getMessage()], 401);
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse($response->toArray());
    }
}
