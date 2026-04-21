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
            'action' => ['sometimes', 'string', 'in:mark-to-charge,close,cancel'],
            'closed_by_user_id' => ['sometimes', 'string', 'uuid'],
        ]);

        try {
            $response = ($this->updateOrder)(
                id: $id,
                diners: $validated['diners'] ?? null,
                action: $validated['action'] ?? null,
                closedByUserId: $validated['closed_by_user_id'] ?? null,
            );
        } catch (\DomainException $exception) {
            return new JsonResponse(['message' => $exception->getMessage()], 422);
        }

        if ($response === null) {
            return new JsonResponse(['message' => 'Order not found.'], 404);
        }

        return new JsonResponse($response->toArray());
    }
}
