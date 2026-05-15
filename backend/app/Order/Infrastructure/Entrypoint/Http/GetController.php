<?php

namespace App\Order\Infrastructure\Entrypoint\Http;

use App\Order\Application\GetOrder\GetOrder;
use App\Order\Domain\Exception\OrderNotFoundException;
use App\Order\Infrastructure\Entrypoint\Http\Requests\GetOrderRequest;
use Illuminate\Http\JsonResponse;

final class GetController
{
    public function __construct(
        private readonly GetOrder $getOrder,
    ) {}

    public function __invoke(GetOrderRequest $request): JsonResponse
    {
        try {
            $response = ($this->getOrder)($request->toCommand());
        } catch (OrderNotFoundException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 404);
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse($response->toArray(), 200);
    }
}
