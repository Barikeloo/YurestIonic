<?php

namespace App\ProductVariant\Infrastructure\Entrypoint\Http;

use App\ProductVariant\Domain\Exception\ProductVariantNotFoundException;
use App\ProductVariant\Application\UpdateProductVariant\UpdateProductVariant;
use App\ProductVariant\Infrastructure\Entrypoint\Http\Requests\UpdateProductVariantRequest;
use Illuminate\Http\JsonResponse;

final class UpdateProductVariantController
{
    public function __construct(
        private UpdateProductVariant $useCase,
    ) {}

    public function __invoke(UpdateProductVariantRequest $request, string $productId, string $variantId): JsonResponse
    {
        try {
            $response = ($this->useCase)($request->toCommand($variantId));

            return new JsonResponse($response->toArray());
        } catch (ProductVariantNotFoundException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 404);
        }
    }
}
