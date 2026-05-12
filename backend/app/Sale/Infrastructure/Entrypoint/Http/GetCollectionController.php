<?php

declare(strict_types=1);

namespace App\Sale\Infrastructure\Entrypoint\Http;

use App\Sale\Application\ListSales\ListSales;
use App\Sale\Infrastructure\Entrypoint\Http\Requests\ListSalesRequest;
use Illuminate\Http\JsonResponse;

final class GetCollectionController
{
    public function __construct(
        private readonly ListSales $listSales,
    ) {}

    public function __invoke(ListSalesRequest $request): JsonResponse
    {
        try {
            $response = ($this->listSales)($request->toCommand());
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse(array_map(
            static fn ($item): array => $item->toArray(),
            $response,
        ), 200);
    }
}
