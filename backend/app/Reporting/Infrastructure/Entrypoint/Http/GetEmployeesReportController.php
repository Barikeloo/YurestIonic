<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure\Entrypoint\Http;

use App\Reporting\Application\GetEmployeesReport\GetEmployeesReport;
use App\Reporting\Application\GetEmployeesReport\GetEmployeesReportCommand;
use App\Reporting\Infrastructure\Entrypoint\Http\Requests\GetEmployeesReportRequest;
use App\Shared\Infrastructure\Tenant\TenantContext;
use Illuminate\Http\JsonResponse;

final readonly class GetEmployeesReportController
{
    public function __construct(private GetEmployeesReport $useCase) {}

    public function __invoke(GetEmployeesReportRequest $request): JsonResponse
    {
        $restaurantId = app(TenantContext::class)->restaurantId();

        if ($restaurantId === null) {
            return response()->json(['error' => 'No restaurant context'], 403);
        }

        try {
            $response = ($this->useCase)(new GetEmployeesReportCommand(
                restaurantId: $restaurantId,
                period:       (string) $request->validated()['period'],
            ));

            return response()->json($response->toArray());
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Throwable) {
            return response()->json(['error' => 'Internal error'], 500);
        }
    }
}
