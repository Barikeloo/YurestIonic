<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure\Entrypoint\Http;

use App\Reporting\Application\GetHeatmap\GetHeatmap;
use App\Reporting\Application\GetHeatmap\GetHeatmapCommand;
use App\Shared\Infrastructure\Tenant\TenantContext;
use Illuminate\Http\JsonResponse;

final readonly class GetHeatmapController
{
    public function __construct(private GetHeatmap $useCase) {}

    public function __invoke(): JsonResponse
    {
        $restaurantId = app(TenantContext::class)->restaurantId();

        if ($restaurantId === null) {
            return response()->json(['error' => 'No restaurant context'], 403);
        }

        try {
            $response = ($this->useCase)(new GetHeatmapCommand(
                restaurantId: $restaurantId,
            ));

            return response()->json($response->toArray());
        } catch (\Throwable) {
            return response()->json(['error' => 'Internal error'], 500);
        }
    }
}
