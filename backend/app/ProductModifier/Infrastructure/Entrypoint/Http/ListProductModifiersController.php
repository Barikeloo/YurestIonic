<?php

namespace App\ProductModifier\Infrastructure\Entrypoint\Http;

use App\Product\Domain\Exception\ProductNotFoundException;
use App\ProductModifier\Application\ListProductModifiers\ListProductModifiers;
use App\ProductModifier\Infrastructure\Entrypoint\Http\Requests\ListProductModifiersRequest;
use Illuminate\Http\JsonResponse;

final class ListProductModifiersController
{
    public function __construct(
        private ListProductModifiers $useCase,
    ) {}

    public function __invoke(ListProductModifiersRequest $request, string $productId): JsonResponse
    {
        try {
            $response = ($this->useCase)($request->toCommand($productId));

            return new JsonResponse($response->toArray());
        } catch (ProductNotFoundException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 404);
        }
    }
}
