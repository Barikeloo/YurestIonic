<?php

declare(strict_types=1);

namespace App\Sale\Infrastructure\Entrypoint\Http;

use App\Sale\Application\GetOrderFinalTicket\GetOrderFinalTicket;
use Illuminate\Http\JsonResponse;

final class GetOrderFinalTicketController
{
    public function __construct(
        private readonly GetOrderFinalTicket $getOrderFinalTicket,
    ) {}

    public function __invoke(string $id): JsonResponse
    {
        $response = ($this->getOrderFinalTicket)($id);

        if ($response === null) {
            return new JsonResponse(['message' => 'Final ticket not found.'], 404);
        }

        return new JsonResponse($response->toArray());
    }
}
