<?php

namespace App\ProductVariant\Infrastructure\Entrypoint\Http;

use App\ProductVariant\Domain\Exception\ProductVariantNotFoundException;
use App\ProductVariant\Application\DeleteProductVariant\DeleteProductVariant;
use App\ProductVariant\Infrastructure\Entrypoint\Http\Requests\DeleteProductVariantRequest;
use Illuminate\Http\JsonResponse;

final class DeleteProductVariantController
{
    public function __construct(
        private DeleteProductVariant $useCase,
    ) {}

    public function __invoke(DeleteProductVariantRequest $request, string $productId, string $variantId): JsonResponse
    {
        try {
            ($this->useCase)($request->toCommand($variantId));

            return new JsonResponse(null, 204);
        } catch (ProductVariantNotFoundException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 404);
        }
    }
}
