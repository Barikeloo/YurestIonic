<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure\Entrypoint\Http;

use App\Reporting\Application\GetSaleDetail\GetSaleDetail;
use App\Reporting\Application\GetSaleDetail\GetSaleDetailCommand;
use App\Shared\Infrastructure\Tenant\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class GetSaleDetailController
{
    public function __construct(private GetSaleDetail $useCase) {}

    public function __invoke(Request $request): JsonResponse
    {
        $restaurantId = app(TenantContext::class)->restaurantId();

        if ($restaurantId === null) {
            return response()->json(['error' => 'No restaurant context'], 403);
        }

        $uuid = (string) $request->route('uuid');

        try {
            $response = ($this->useCase)(new GetSaleDetailCommand(
                restaurantId: $restaurantId,
                saleUuid:    $uuid,
            ));

            return response()->json($response->toArray());
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        } catch (\Throwable) {
            return response()->json(['error' => 'Internal error'], 500);
        }
    }
}
