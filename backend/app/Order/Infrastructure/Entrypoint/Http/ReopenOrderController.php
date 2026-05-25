<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Entrypoint\Http;

use App\Order\Application\ReopenOrder\ReopenOrder;
use App\Order\Domain\Exception\OrderNotFoundException;
use App\Order\Infrastructure\Entrypoint\Http\Requests\ReopenOrderRequest;
use Illuminate\Http\JsonResponse;

final class ReopenOrderController
{
    public function __construct(
        private readonly ReopenOrder $reopenOrder,
    ) {}

    public function __invoke(ReopenOrderRequest $request): JsonResponse
    {
        try {
            $response = ($this->reopenOrder)($request->toCommand());
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
