<?php

namespace App\Order\Infrastructure\Entrypoint\Http;

use App\Order\Application\ListOrders\ListOrders;
use App\Order\Infrastructure\Entrypoint\Http\Requests\ListOrdersRequest;
use Illuminate\Http\JsonResponse;

final class GetCollectionController
{
    public function __construct(
        private readonly ListOrders $listOrders,
    ) {}

    public function __invoke(ListOrdersRequest $request): JsonResponse
    {
        try {
            $response = ($this->listOrders)($request->toCommand());
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse(
            array_map(static fn ($item) => $item->toArray(), $response),
            200,
        );
    }
}
