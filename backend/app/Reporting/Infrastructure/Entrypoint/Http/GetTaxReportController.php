<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure\Entrypoint\Http;

use App\Reporting\Application\GetTaxReport\GetTaxReport;
use App\Reporting\Application\GetTaxReport\GetTaxReportCommand;
use App\Reporting\Infrastructure\Entrypoint\Http\Requests\GetTaxReportRequest;
use App\Shared\Infrastructure\Tenant\TenantContext;
use Illuminate\Http\JsonResponse;

final readonly class GetTaxReportController
{
    public function __construct(private GetTaxReport $useCase) {}

    public function __invoke(GetTaxReportRequest $request): JsonResponse
    {
        $restaurantId = app(TenantContext::class)->restaurantId();

        if ($restaurantId === null) {
            return response()->json(['error' => 'No restaurant context'], 403);
        }

        try {
            $response = ($this->useCase)(new GetTaxReportCommand(
                restaurantId: $restaurantId,
                period:       (string) $request->validated()['period'],
                quarter:      $request->validatedQuarter(),
            ));

            return response()->json($response->toArray());
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Throwable) {
            return response()->json(['error' => 'Internal error'], 500);
        }
    }
}
