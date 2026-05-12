<?php

declare(strict_types=1);

namespace App\Sale\Infrastructure\Entrypoint\Http;

use App\Sale\Application\UpdateSale\UpdateSale;
use App\Sale\Domain\Exception\SaleAlreadyClosedException;
use App\Sale\Domain\Exception\SaleMustHaveLinesException;
use App\Sale\Domain\Exception\SaleNotFoundException;
use App\Sale\Infrastructure\Entrypoint\Http\Requests\UpdateSaleRequest;
use Illuminate\Http\JsonResponse;

final class PutController
{
    public function __construct(
        private readonly UpdateSale $updateSale,
    ) {}

    public function __invoke(UpdateSaleRequest $request): JsonResponse
    {
        try {
            $response = ($this->updateSale)($request->toCommand());
        } catch (SaleNotFoundException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 404);
        } catch (SaleAlreadyClosedException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 409);
        } catch (SaleMustHaveLinesException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse($response->toArray(), 200);
    }
}
