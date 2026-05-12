<?php

declare(strict_types=1);

namespace App\Cash\Infrastructure\Entrypoint\Http;

use App\Cash\Application\ListCashSessions\ListCashSessions;
use App\Cash\Infrastructure\Entrypoint\Http\Requests\ListCashSessionsRequest;
use Illuminate\Http\JsonResponse;

final class ListCashSessionsController
{
    public function __construct(
        private readonly ListCashSessions $listCashSessions,
    ) {}

    public function __invoke(ListCashSessionsRequest $request): JsonResponse
    {
        try {
            $response = ($this->listCashSessions)($request->toCommand());
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse($response->toArray(), 200);
    }
}
