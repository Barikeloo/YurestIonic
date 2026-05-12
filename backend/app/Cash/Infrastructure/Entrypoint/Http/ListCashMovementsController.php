<?php

declare(strict_types=1);

namespace App\Cash\Infrastructure\Entrypoint\Http;

use App\Cash\Application\ListCashMovements\ListCashMovements;
use App\Cash\Infrastructure\Entrypoint\Http\Requests\ListCashMovementsRequest;
use Illuminate\Http\JsonResponse;

final class ListCashMovementsController
{
    public function __construct(
        private readonly ListCashMovements $listCashMovements,
    ) {}

    public function __invoke(ListCashMovementsRequest $request): JsonResponse
    {
        try {
            $response = ($this->listCashMovements)($request->toCommand());
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse($response->toArray(), 200);
    }
}
