<?php

namespace App\Order\Infrastructure\Entrypoint\Http;

use App\Order\Application\UpdateOrder\UpdateOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PutController
{
    public function __construct(
        private readonly UpdateOrder $updateOrder,
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'diners' => ['sometimes', 'integer', 'min:1'],
            'action' => ['sometimes', 'string', 'in:close,cancel'],
            'closed_by_user_id' => ['sometimes', 'string', 'uuid'],
        ]);

        $response = ($this->updateOrder)(
            id: $id,
            diners: $validated['diners'] ?? null,
            action: $validated['action'] ?? null,
            closedByUserId: $validated['closed_by_user_id'] ?? null,
        );

        if ($response === null) {
            return new JsonResponse(['message' => 'Order not found.'], 404);
        }

        return new JsonResponse($response->toArray());
    }
}
