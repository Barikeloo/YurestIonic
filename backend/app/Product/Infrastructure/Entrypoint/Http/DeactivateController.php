<?php

namespace App\Product\Infrastructure\Entrypoint\Http;

use App\Product\Application\SetProductActive\SetProductActive;
use Illuminate\Http\JsonResponse;

class DeactivateController
{
    public function __construct(
        private SetProductActive $setProductActive,
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        $response = ($this->setProductActive)($id, false);

        if ($response === null) {
            return new JsonResponse(['message' => 'Product not found.'], 404);
        }

        return new JsonResponse($response->toArray());
    }
}
