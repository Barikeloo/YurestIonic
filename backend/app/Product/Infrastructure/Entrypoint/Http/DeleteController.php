<?php

namespace App\Product\Infrastructure\Entrypoint\Http;

use App\Product\Application\DeleteProduct\DeleteProduct;
use Illuminate\Http\JsonResponse;

class DeleteController
{
    public function __construct(
        private DeleteProduct $deleteProduct,
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        $deleted = ($this->deleteProduct)($id);

        if (! $deleted) {
            return new JsonResponse(['message' => 'Product not found.'], 404);
        }

        return new JsonResponse(null, 204);
    }
}
