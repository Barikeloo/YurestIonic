<?php

declare(strict_types=1);

namespace App\Sale\Infrastructure\Entrypoint\Http;

use App\Sale\Application\CancelSale\CancelSale;
use App\Sale\Domain\Exception\SaleAlreadyCancelledException;
use App\Sale\Domain\Exception\SaleNotFoundException;
use App\Sale\Infrastructure\Entrypoint\Http\Requests\CancelSaleRequest;
use Illuminate\Http\JsonResponse;

final class CancelSaleController
{
    public function __construct(
        private readonly CancelSale $cancelSale,
    ) {}

    public function __invoke(CancelSaleRequest $request): JsonResponse
    {
        try {
            $response = ($this->cancelSale)($request->toCommand());
        } catch (SaleNotFoundException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 404);
        } catch (SaleAlreadyCancelledException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 409);
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse($response->toArray(), 200);
    }
}
