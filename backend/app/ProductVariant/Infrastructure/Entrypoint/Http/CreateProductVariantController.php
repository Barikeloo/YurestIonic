<?php

namespace App\ProductVariant\Infrastructure\Entrypoint\Http;

use App\Product\Domain\Exception\ProductNotFoundException;
use App\ProductVariant\Application\CreateProductVariant\CreateProductVariant;
use App\ProductVariant\Infrastructure\Entrypoint\Http\Requests\CreateProductVariantRequest;
use Illuminate\Http\JsonResponse;

final class CreateProductVariantController
{
    public function __construct(
        private CreateProductVariant $useCase,
    ) {}

    public function __invoke(CreateProductVariantRequest $request, string $productId): JsonResponse
    {
        try {
            $response = ($this->useCase)($request->toCommand($productId));

            return new JsonResponse($response->toArray(), 201);
        } catch (ProductNotFoundException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 404);
        }
    }
}
