<?php

declare(strict_types=1);

namespace App\Cash\Infrastructure\Entrypoint\Http;

use App\Cash\Application\GetLastClosedCashSession\GetLastClosedCashSession;
use App\Shared\Infrastructure\Tenant\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class GetLastClosedCashSessionController
{
    public function __construct(
        private readonly GetLastClosedCashSession $getLastClosedCashSession,
        private readonly TenantContext $tenantContext,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $response = ($this->getLastClosedCashSession)(
            restaurantId: $this->tenantContext->restaurantUuid(),
        );

        return new JsonResponse($response->toArray(), 200);
    }
}
