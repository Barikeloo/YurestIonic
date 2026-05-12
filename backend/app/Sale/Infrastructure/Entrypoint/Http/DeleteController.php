<?php

declare(strict_types=1);

namespace App\Sale\Infrastructure\Entrypoint\Http;

use App\Sale\Application\DeleteSale\DeleteSale;
use App\Sale\Domain\Exception\SaleNotFoundException;
use App\Sale\Infrastructure\Entrypoint\Http\Requests\DeleteSaleRequest;
use Illuminate\Http\JsonResponse;

final class DeleteController
{
    public function __construct(
        private readonly DeleteSale $deleteSale,
    ) {}

    public function __invoke(DeleteSaleRequest $request): JsonResponse
    {
        try {
            ($this->deleteSale)($request->toCommand());
        } catch (SaleNotFoundException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 404);
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse(status: 204);
    }
}
