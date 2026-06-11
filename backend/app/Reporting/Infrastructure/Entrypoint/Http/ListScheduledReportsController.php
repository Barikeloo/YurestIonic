<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure\Entrypoint\Http;

use App\Reporting\Application\ListScheduledReports\ListScheduledReports;
use App\Reporting\Application\ListScheduledReports\ListScheduledReportsCommand;
use App\Shared\Infrastructure\Tenant\TenantContext;
use Illuminate\Http\JsonResponse;

final readonly class ListScheduledReportsController
{
    public function __construct(
        private ListScheduledReports $useCase,
    ) {}

    public function __invoke(): JsonResponse
    {
        $restaurantId = app(TenantContext::class)->restaurantId();

        if ($restaurantId === null) {
            return new JsonResponse(['error' => 'No restaurant context'], 403);
        }

        $response = ($this->useCase)(new ListScheduledReportsCommand(
            restaurantId: $restaurantId,
        ));

        return new JsonResponse($response->toArray(), 200);
    }
}
