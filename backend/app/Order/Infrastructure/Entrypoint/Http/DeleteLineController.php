<?php

namespace App\Order\Infrastructure\Entrypoint\Http;

use App\Order\Application\DeleteOrderLine\DeleteOrderLine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DeleteLineController
{
    public function __construct(
        private readonly DeleteOrderLine $deleteOrderLine,
    ) {}

    public function __invoke(Request $request, string $lineId): JsonResponse
    {
        $deleted = ($this->deleteOrderLine)($lineId);

        if (!$deleted) {
            return new JsonResponse(['message' => 'Order line not found.'], 404);
        }

        return new JsonResponse(null, 204);
    }
}
