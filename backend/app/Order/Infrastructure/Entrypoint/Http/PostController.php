<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Entrypoint\Http;

use App\Order\Application\CreateOrder\CreateOrder;
use App\Order\Domain\Exception\TableAlreadyHasOpenOrderException;
use App\Order\Infrastructure\Entrypoint\Http\Requests\CreateOrderRequest;
use Illuminate\Http\JsonResponse;

final class PostController
{
    public function __construct(
        private readonly CreateOrder $createOrder,
    ) {}

    public function __invoke(CreateOrderRequest $request): JsonResponse
    {
        try {
            $response = ($this->createOrder)($request->toCommand());
        } catch (TableAlreadyHasOpenOrderException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 409);
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse($response->toArray(), 201);
    }
}
