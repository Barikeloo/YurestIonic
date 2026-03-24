<?php

namespace App\Product\Infrastructure\Entrypoint\Http;

use App\Product\Application\GetProduct\GetProduct;
use Illuminate\Http\JsonResponse;

class GetController
{
    public function __construct(
        private GetProduct $getProduct,
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        $response = ($this->getProduct)($id);

        if ($response === null) {
            return new JsonResponse(['message' => 'Product not found.'], 404);
        }

        return new JsonResponse($response->toArray());
    }
}
