<?php

namespace App\Order\Infrastructure\Entrypoint\Http;

use App\Order\Application\GetOrder\GetOrder;
use Illuminate\Http\JsonResponse;

final class GetController
{
    public function __construct(
        private readonly GetOrder $getOrder,
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        $response = ($this->getOrder)($id);

        if ($response === null) {
            return new JsonResponse(['message' => 'Order not found.'], 404);
        }

        return new JsonResponse($response->toArray());
    }
}
