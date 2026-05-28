<?php

declare(strict_types=1);

namespace App\Audit\Infrastructure\Entrypoint\Http;

use App\Audit\Application\ListAuditEvents\ListAuditEvents;
use App\Audit\Infrastructure\Entrypoint\Http\Requests\ListAuditEventsRequest;
use Illuminate\Http\JsonResponse;

final class ListAuditEventsController
{
    public function __construct(
        private readonly ListAuditEvents $useCase,
    ) {}

    public function __invoke(ListAuditEventsRequest $request): JsonResponse
    {
        try {
            $response = ($this->useCase)($request->toCommand());
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse($response->toArray(), 200);
    }
}
