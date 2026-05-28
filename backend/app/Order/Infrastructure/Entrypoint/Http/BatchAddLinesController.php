<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Entrypoint\Http;

use App\Family\Domain\Exception\FamilyNotActiveException;
use App\Family\Domain\Exception\FamilyNotFoundException;
use App\Menu\Domain\Exception\MenuNotFoundException;
use App\Order\Application\BatchAddLinesToOrder\BatchAddLinesToOrder;
use App\Order\Domain\Exception\OrderIsNotOpenException;
use App\Order\Domain\Exception\OrderNotFoundException;
use App\Order\Infrastructure\Entrypoint\Http\Requests\BatchAddLinesRequest;
use App\Product\Domain\Exception\InsufficientStockException;
use App\Product\Domain\Exception\ProductNotActiveException;
use App\Product\Domain\Exception\ProductNotFoundException;
use App\Tax\Domain\Exception\TaxNotFoundException;
use DomainException;
use Illuminate\Http\JsonResponse;

final class BatchAddLinesController
{
    public function __construct(
        private readonly BatchAddLinesToOrder $batchAddLinesToOrder,
    ) {}

    public function __invoke(BatchAddLinesRequest $request): JsonResponse
    {
        try {
            $response = ($this->batchAddLinesToOrder)($request->toCommand());
        } catch (OrderNotFoundException|ProductNotFoundException|MenuNotFoundException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 404);
        } catch (
            OrderIsNotOpenException|
            ProductNotActiveException|
            FamilyNotActiveException|
            InsufficientStockException|
            TaxNotFoundException|
            DomainException $e
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
