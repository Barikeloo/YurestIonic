<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Entrypoint\Http;

use App\Order\Application\GetOrderTransfers\GetOrderTransfers;
use App\Order\Domain\Exception\OrderNotFoundException;
use App\Order\Infrastructure\Entrypoint\Http\Requests\GetOrderTransfersRequest;
use Illuminate\Http\JsonResponse;

final class GetOrderTransfersController
{
    public function __construct(
        private readonly GetOrderTransfers $useCase,
    ) {}

    public function __invoke(GetOrderTransfersRequest $request): JsonResponse
    {
        try {
            $response = ($this->useCase)($request->toCommand());
        } catch (OrderNotFoundException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 404);
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse($response->toArray(), 200);
    }
}
