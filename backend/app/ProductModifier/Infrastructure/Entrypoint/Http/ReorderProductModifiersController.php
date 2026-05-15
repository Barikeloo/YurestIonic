<?php

namespace App\ProductModifier\Infrastructure\Entrypoint\Http;

use App\ProductModifier\Application\ReorderProductModifiers\ReorderProductModifiers;
use App\ProductModifier\Domain\Exception\ProductModifierNotFoundException;
use App\ProductModifier\Infrastructure\Entrypoint\Http\Requests\ReorderProductModifiersRequest;
use Illuminate\Http\JsonResponse;

final class ReorderProductModifiersController
{
    public function __construct(
        private ReorderProductModifiers $useCase,
    ) {}

    public function __invoke(ReorderProductModifiersRequest $request, string $productId): JsonResponse
    {
        try {
            ($this->useCase)($request->toCommand($productId));

            return new JsonResponse(null, 204);
        } catch (ProductModifierNotFoundException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 404);
        }
    }
}
