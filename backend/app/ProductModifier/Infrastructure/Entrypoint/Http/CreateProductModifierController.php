<?php

namespace App\ProductModifier\Infrastructure\Entrypoint\Http;

use App\Product\Domain\Exception\ProductNotFoundException;
use App\ProductModifier\Application\CreateProductModifier\CreateProductModifier;
use App\ProductModifier\Infrastructure\Entrypoint\Http\Requests\CreateProductModifierRequest;
use Illuminate\Http\JsonResponse;

final class CreateProductModifierController
{
    public function __construct(
        private CreateProductModifier $useCase,
    ) {}

    public function __invoke(CreateProductModifierRequest $request, string $productId): JsonResponse
    {
        try {
            $response = ($this->useCase)($request->toCommand($productId));

            return new JsonResponse($response->toArray(), 201);
        } catch (ProductNotFoundException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 404);
        }
    }
}
