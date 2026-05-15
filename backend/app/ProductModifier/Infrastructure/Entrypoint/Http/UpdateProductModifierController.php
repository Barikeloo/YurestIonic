<?php

namespace App\ProductModifier\Infrastructure\Entrypoint\Http;

use App\ProductModifier\Domain\Exception\ProductModifierNotFoundException;
use App\ProductModifier\Application\UpdateProductModifier\UpdateProductModifier;
use App\ProductModifier\Infrastructure\Entrypoint\Http\Requests\UpdateProductModifierRequest;
use Illuminate\Http\JsonResponse;

final class UpdateProductModifierController
{
    public function __construct(
        private UpdateProductModifier $useCase,
    ) {}

    public function __invoke(UpdateProductModifierRequest $request, string $productId, string $modifierId): JsonResponse
    {
        try {
            $response = ($this->useCase)($request->toCommand($modifierId));

            return new JsonResponse($response->toArray());
        } catch (ProductModifierNotFoundException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 404);
        }
    }
}
