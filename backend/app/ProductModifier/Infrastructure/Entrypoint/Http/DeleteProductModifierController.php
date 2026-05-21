<?php

namespace App\ProductModifier\Infrastructure\Entrypoint\Http;

use App\ProductModifier\Application\DeleteProductModifier\DeleteProductModifier;
use App\ProductModifier\Domain\Exception\ProductModifierNotFoundException;
use App\ProductModifier\Infrastructure\Entrypoint\Http\Requests\DeleteProductModifierRequest;
use Illuminate\Http\JsonResponse;

final class DeleteProductModifierController
{
    public function __construct(
        private DeleteProductModifier $useCase,
    ) {}

    public function __invoke(DeleteProductModifierRequest $request, string $productId, string $modifierId): JsonResponse
    {
        try {
            ($this->useCase)($request->toCommand($modifierId));

            return new JsonResponse(null, 204);
        } catch (ProductModifierNotFoundException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 404);
        }
    }
}
