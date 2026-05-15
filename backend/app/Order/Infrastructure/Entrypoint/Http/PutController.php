<?php

namespace App\Order\Infrastructure\Entrypoint\Http;

use App\Order\Application\UpdateOrder\UpdateOrder;
use App\Order\Domain\Exception\OrderNotFoundException;
use App\Order\Infrastructure\Entrypoint\Http\Requests\UpdateOrderRequest;
use Illuminate\Http\JsonResponse;

final class PutController
{
    public function __construct(
        private readonly UpdateOrder $updateOrder,
    ) {}

    public function __invoke(UpdateOrderRequest $request): JsonResponse
    {
        try {
            $response = ($this->updateOrder)($request->toCommand());
        } catch (OrderNotFoundException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 404);
        } catch (\DomainException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse($response->toArray(), 200);
    }
}
