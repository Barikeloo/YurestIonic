<?php

namespace App\Tax\Infrastructure\Entrypoint\Http;

use App\Tax\Application\DeleteTax\DeleteTax;
use Illuminate\Http\JsonResponse;

class DeleteController
{
    public function __construct(
        private DeleteTax $deleteTax,
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        $deleted = ($this->deleteTax)($id);

        if (! $deleted) {
            return new JsonResponse(['message' => 'Tax not found.'], 404);
        }

        return new JsonResponse(null, 204);
    }
}
