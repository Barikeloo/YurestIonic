<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Entrypoint\Http;

use App\Menu\Domain\Exception\MenuNotFoundException;
use App\Order\Application\AddMenuLineToOrder\AddMenuLineToOrder;
use App\Order\Domain\Exception\OrderIsNotOpenException;
use App\Order\Domain\Exception\OrderNotFoundException;
use App\Order\Infrastructure\Entrypoint\Http\Requests\AddMenuLineToOrderRequest;
use App\Product\Domain\Exception\InsufficientStockException;
use App\Product\Domain\Exception\ProductNotActiveException;
use App\Product\Domain\Exception\ProductNotFoundException;
use App\Tax\Domain\Exception\TaxNotFoundException;
use DomainException;
use Illuminate\Http\JsonResponse;

final class AddMenuLineController
{
    public function __construct(
        private readonly AddMenuLineToOrder $addMenuLineToOrder,
    ) {}

    public function __invoke(AddMenuLineToOrderRequest $request): JsonResponse
    {
        try {
            $response = ($this->addMenuLineToOrder)($request->toCommand());
        } catch (OrderNotFoundException|MenuNotFoundException|ProductNotFoundException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 404);
        } catch (
            OrderIsNotOpenException|
            ProductNotActiveException|
            InsufficientStockException|
            TaxNotFoundException|
            DomainException $e
        ) {
            return new JsonResponse(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse($response->toArray(), 201);
    }
}
