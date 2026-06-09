<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure\Entrypoint\Http;

use App\Reporting\Application\GetSalesReport\GetSalesReport;
use App\Reporting\Application\GetSalesReport\GetSalesReportCommand;
use App\Reporting\Infrastructure\Entrypoint\Http\Requests\GetSalesReportRequest;
use App\Shared\Infrastructure\Tenant\TenantContext;
use Illuminate\Http\JsonResponse;

final readonly class GetSalesReportController
{
    public function __construct(private GetSalesReport $useCase) {}

    public function __invoke(GetSalesReportRequest $request): JsonResponse
    {
        $restaurantId = app(TenantContext::class)->restaurantId();

        if ($restaurantId === null) {
            return response()->json(['error' => 'No restaurant context'], 403);
        }

        try {
            $response = ($this->useCase)(new GetSalesReportCommand(
                restaurantId: $restaurantId,
                period:       (string) $request->validated()['period'],
                page:         (int) ($request->validated()['page'] ?? 1),
                perPage:      (int) ($request->validated()['per_page'] ?? 50),
            ));

            return response()->json($response->toArray());
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Throwable) {
            return response()->json(['error' => 'Internal error'], 500);
        }
    }
}
