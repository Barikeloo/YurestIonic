<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Entrypoint\Http;

use App\Order\Application\CancelOrder\CancelOrder;
use App\Order\Domain\Exception\OrderNotFoundException;
use App\Order\Infrastructure\Entrypoint\Http\Requests\CancelOrderRequest;
use Illuminate\Http\JsonResponse;

final class CancelOrderController
{
    public function __construct(
        private readonly CancelOrder $cancelOrder,
    ) {}

    public function __invoke(CancelOrderRequest $request): JsonResponse
    {
        try {
            $response = ($this->cancelOrder)($request->toCommand());
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
