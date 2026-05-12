<?php

declare(strict_types=1);

namespace App\Sale\Infrastructure\Entrypoint\Http;

use App\Sale\Application\GetOrderFinalTicket\GetOrderFinalTicket;
use App\Sale\Domain\Exception\OrderFinalTicketNotFoundException;
use App\Sale\Infrastructure\Entrypoint\Http\Requests\GetOrderFinalTicketRequest;
use Illuminate\Http\JsonResponse;

final class GetOrderFinalTicketController
{
    public function __construct(
        private readonly GetOrderFinalTicket $getOrderFinalTicket,
    ) {}

    public function __invoke(GetOrderFinalTicketRequest $request): JsonResponse
    {
        try {
            $response = ($this->getOrderFinalTicket)($request->toCommand());
        } catch (OrderFinalTicketNotFoundException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 404);
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse($response->toArray(), 200);
    }
}
