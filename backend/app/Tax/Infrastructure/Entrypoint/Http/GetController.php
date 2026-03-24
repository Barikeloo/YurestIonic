<?php

namespace App\Tax\Infrastructure\Entrypoint\Http;

use App\Tax\Application\GetTax\GetTax;
use Illuminate\Http\JsonResponse;

class GetController
{
    public function __construct(
        private GetTax $getTax,
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        $response = ($this->getTax)($id);

        if ($response === null) {
            return new JsonResponse(['message' => 'Tax not found.'], 404);
        }

        return new JsonResponse($response->toArray());
    }
}
