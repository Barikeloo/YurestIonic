<?php

namespace App\Order\Infrastructure\Entrypoint\Http;

use App\Order\Application\DeleteOrder\DeleteOrder;
use App\Order\Domain\Exception\OrderIsNotOpenException;
use App\Order\Domain\Exception\OrderNotFoundException;
use App\Order\Infrastructure\Entrypoint\Http\Requests\DeleteOrderRequest;
use Illuminate\Http\JsonResponse;

final class DeleteController
{
    public function __construct(
        private readonly DeleteOrder $deleteOrder,
    ) {}

    public function __invoke(DeleteOrderRequest $request): JsonResponse
    {
        try {
            ($this->deleteOrder)($request->toCommand());
        } catch (OrderNotFoundException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 404);
        } catch (OrderIsNotOpenException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse(null, 204);
    }
}
