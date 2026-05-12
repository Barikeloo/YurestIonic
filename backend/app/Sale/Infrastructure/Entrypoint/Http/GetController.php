<?php

declare(strict_types=1);

namespace App\Sale\Infrastructure\Entrypoint\Http;

use App\Sale\Application\GetSale\GetSale;
use App\Sale\Domain\Exception\SaleNotFoundException;
use App\Sale\Infrastructure\Entrypoint\Http\Requests\GetSaleRequest;
use Illuminate\Http\JsonResponse;

final class GetController
{
    public function __construct(
        private readonly GetSale $getSale,
    ) {}

    public function __invoke(GetSaleRequest $request): JsonResponse
    {
        try {
            $response = ($this->getSale)($request->toCommand());
        } catch (SaleNotFoundException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 404);
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse($response->toArray(), 200);
    }
}
