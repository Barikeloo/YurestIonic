<?php

namespace App\ProductVariant\Infrastructure\Entrypoint\Http;

use App\Product\Domain\Exception\ProductNotFoundException;
use App\ProductVariant\Application\ListProductVariants\ListProductVariants;
use App\ProductVariant\Infrastructure\Entrypoint\Http\Requests\ListProductVariantsRequest;
use Illuminate\Http\JsonResponse;

final class ListProductVariantsController
{
    public function __construct(
        private ListProductVariants $useCase,
    ) {}

    public function __invoke(ListProductVariantsRequest $request, string $productId): JsonResponse
    {
        try {
            $response = ($this->useCase)($request->toCommand($productId));

            return new JsonResponse($response->toArray());
        } catch (ProductNotFoundException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 404);
        }
    }
}
