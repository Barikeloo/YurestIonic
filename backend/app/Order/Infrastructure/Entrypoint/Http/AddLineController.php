<?php

namespace App\Order\Infrastructure\Entrypoint\Http;

use App\Order\Application\AddLineToOrder\AddLineToOrder;
use App\Family\Domain\Exception\FamilyNotActiveException;
use App\Family\Domain\Exception\FamilyNotFoundException;
use App\Order\Domain\Exception\OrderIsNotOpenException;
use App\Order\Domain\Exception\OrderNotFoundException;
use App\Order\Infrastructure\Entrypoint\Http\Requests\AddLineToOrderRequest;
use App\Product\Domain\Exception\InsufficientStockException;
use App\Product\Domain\Exception\ProductNotActiveException;
use App\Product\Domain\Exception\ProductNotFoundException;
use App\Tax\Domain\Exception\TaxNotFoundException;
use Illuminate\Http\JsonResponse;

final class AddLineController
{
    public function __construct(
        private readonly AddLineToOrder $addLineToOrder,
    ) {}

    public function __invoke(AddLineToOrderRequest $request): JsonResponse
    {
        try {
            $response = ($this->addLineToOrder)($request->toCommand());
        } catch (OrderNotFoundException | ProductNotFoundException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 404);
        } catch (
            OrderIsNotOpenException |
            ProductNotActiveException |
            FamilyNotActiveException |
            InsufficientStockException |
            TaxNotFoundException $e
        ) {
            return new JsonResponse(['message' => $e->getMessage()], 422);
        } catch (FamilyNotFoundException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 404);
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse($response->toArray(), 201);
    }
}
