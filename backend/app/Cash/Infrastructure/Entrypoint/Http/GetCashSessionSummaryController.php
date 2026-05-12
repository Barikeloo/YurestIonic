<?php

declare(strict_types=1);

namespace App\Cash\Infrastructure\Entrypoint\Http;

use App\Cash\Application\GetCashSessionSummary\GetCashSessionSummary;
use App\Cash\Domain\Exception\CashSessionNotFoundException;
use App\Cash\Infrastructure\Entrypoint\Http\Requests\GetCashSessionSummaryRequest;
use Illuminate\Http\JsonResponse;

final class GetCashSessionSummaryController
{
    public function __construct(
        private readonly GetCashSessionSummary $getCashSessionSummary,
    ) {}

    public function __invoke(GetCashSessionSummaryRequest $request): JsonResponse
    {
        try {
            $response = ($this->getCashSessionSummary)($request->toCommand());
        } catch (CashSessionNotFoundException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 404);
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse($response->toArray(), 200);
    }
}
