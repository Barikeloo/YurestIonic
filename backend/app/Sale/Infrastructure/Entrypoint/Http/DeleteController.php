<?php

namespace App\Sale\Infrastructure\Entrypoint\Http;

use App\Sale\Application\DeleteSale\DeleteSale;
use Illuminate\Http\JsonResponse;

final class DeleteController
{
    public function __construct(
        private readonly DeleteSale $deleteSale,
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        $deleted = ($this->deleteSale)($id);

        if (! $deleted) {
            return new JsonResponse(['message' => 'Sale not found.'], 404);
        }

        return new JsonResponse(status: 204);
    }
}
