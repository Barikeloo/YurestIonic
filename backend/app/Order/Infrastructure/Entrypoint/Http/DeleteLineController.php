<?php

namespace App\Order\Infrastructure\Entrypoint\Http;

use App\Order\Application\DeleteOrderLine\DeleteOrderLine;
use App\Order\Domain\Exception\OrderIsNotOpenException;
use App\Order\Domain\Exception\OrderLineNotFoundException;
use App\Order\Domain\Exception\OrderNotFoundException;
use App\Order\Infrastructure\Entrypoint\Http\Requests\DeleteOrderLineRequest;
use Illuminate\Http\JsonResponse;

final class DeleteLineController
{
    public function __construct(
        private readonly DeleteOrderLine $deleteOrderLine,
    ) {}

    public function __invoke(DeleteOrderLineRequest $request): JsonResponse
    {
        try {
            ($this->deleteOrderLine)($request->toCommand());
        } catch (OrderLineNotFoundException | OrderNotFoundException $e) {
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
