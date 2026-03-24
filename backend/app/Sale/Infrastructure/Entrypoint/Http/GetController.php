<?php

namespace App\Sale\Infrastructure\Entrypoint\Http;

use App\Sale\Application\GetSale\GetSale;
use Illuminate\Http\JsonResponse;

final class GetController
{
    public function __construct(
        private readonly GetSale $getSale,
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        $response = ($this->getSale)($id);

        if ($response === null) {
            return new JsonResponse(['message' => 'Sale not found.'], 404);
        }

        return new JsonResponse($response->toArray());
    }
}
