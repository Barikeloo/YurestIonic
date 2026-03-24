<?php

namespace App\Sale\Infrastructure\Entrypoint\Http;

use App\Sale\Application\UpdateSale\UpdateSale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PutController
{
    public function __construct(
        private readonly UpdateSale $updateSale,
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'ticket_number' => ['sometimes', 'integer', 'min:1'],
            'total' => ['sometimes', 'integer', 'min:0'],
        ]);

        $response = ($this->updateSale)(
            id: $id,
            ticketNumber: $validated['ticket_number'] ?? null,
            total: $validated['total'] ?? null,
        );

        if ($response === null) {
            return new JsonResponse(['message' => 'Sale not found.'], 404);
        }

        return new JsonResponse($response->toArray());
    }
}
