<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure\Entrypoint\Http;

use App\Reporting\Application\GetDashboardSummary\GetDashboardSummary;
use App\Reporting\Application\GetDashboardSummary\GetDashboardSummaryCommand;
use App\Reporting\Infrastructure\Entrypoint\Http\Requests\GetDashboardSummaryRequest;
use App\Shared\Infrastructure\Tenant\TenantContext;
use Illuminate\Http\JsonResponse;

final readonly class GetDashboardSummaryController
{
    public function __construct(private GetDashboardSummary $useCase) {}

    public function __invoke(GetDashboardSummaryRequest $request): JsonResponse
    {
        $restaurantId = app(TenantContext::class)->restaurantId();

        if ($restaurantId === null) {
            return response()->json(['error' => 'No restaurant context'], 403);
        }

        try {
            $response = ($this->useCase)(new GetDashboardSummaryCommand(
                restaurantId: $restaurantId,
                period:       $request->validated()['period'],
            ));

            return response()->json($response->toArray());
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Throwable) {
            return response()->json(['error' => 'Internal error'], 500);
        }
    }
}
