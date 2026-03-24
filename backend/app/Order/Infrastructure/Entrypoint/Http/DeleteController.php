<?php

namespace App\Order\Infrastructure\Entrypoint\Http;

use App\Order\Application\DeleteOrder\DeleteOrder;
use Illuminate\Http\JsonResponse;

final class DeleteController
{
    public function __construct(
        private readonly DeleteOrder $deleteOrder,
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        $deleted = ($this->deleteOrder)($id);

        if (!$deleted) {
            return new JsonResponse(['message' => 'Order not found.'], 404);
        }

        return new JsonResponse(status: 204);
    }
}
