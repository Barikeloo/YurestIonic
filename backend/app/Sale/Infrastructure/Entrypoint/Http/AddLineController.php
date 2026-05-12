<?php

declare(strict_types=1);

namespace App\Sale\Infrastructure\Entrypoint\Http;

use App\Sale\Application\AddLineToSale\AddLineToSale;
use App\Sale\Domain\Exception\OrderLineNotFoundException;
use App\Sale\Domain\Exception\ProductNotActiveException;
use App\Sale\Infrastructure\Entrypoint\Http\Requests\AddLineToSaleRequest;
use Illuminate\Http\JsonResponse;

final class AddLineController
{
    public function __construct(
        private readonly AddLineToSale $addLineToSale,
    ) {}

    public function __invoke(AddLineToSaleRequest $request): JsonResponse
    {
        try {
            $response = ($this->addLineToSale)($request->toCommand());
        } catch (OrderLineNotFoundException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 404);
        } catch (ProductNotActiveException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse($response->toArray(), 201);
    }
}
