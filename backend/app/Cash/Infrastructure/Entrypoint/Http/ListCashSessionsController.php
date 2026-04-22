<?php

declare(strict_types=1);

namespace App\Cash\Infrastructure\Entrypoint\Http;

use App\Cash\Application\ListCashSessions\ListCashSessions;
use App\Shared\Infrastructure\Tenant\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ListCashSessionsController
{
    public function __construct(
        private readonly ListCashSessions $listCashSessions,
        private readonly TenantContext $tenantContext,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $response = ($this->listCashSessions)(
            restaurantId: $this->tenantContext->restaurantUuid(),
        );

        return new JsonResponse($response->toArray(), 200);
    }
}
