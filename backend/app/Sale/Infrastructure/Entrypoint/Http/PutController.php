<?php

namespace App\Sale\Infrastructure\Entrypoint\Http;

use App\Sale\Application\UpdateSale\UpdateSale;
use InvalidArgumentException;
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
            'closed_by_user_id' => ['required', 'string', 'uuid'],
            'ticket_number' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $response = ($this->updateSale)(
                id: $id,
                closedByUserId: $validated['closed_by_user_id'],
                ticketNumber: $validated['ticket_number'],
            );
        } catch (InvalidArgumentException $exception) {
            return new JsonResponse([
                'message' => $exception->getMessage(),
            ], 422);
        }

        if ($response === null) {
            return new JsonResponse(['message' => 'Sale not found.'], 404);
        }

        return new JsonResponse($response->toArray());
    }
}
