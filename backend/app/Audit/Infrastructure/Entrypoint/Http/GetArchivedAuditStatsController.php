<?php

declare(strict_types=1);

namespace App\Audit\Infrastructure\Entrypoint\Http;

use App\Audit\Application\GetArchivedAuditStats\GetArchivedAuditStats;
use App\Audit\Infrastructure\Entrypoint\Http\Requests\GetArchivedAuditStatsRequest;
use Illuminate\Http\JsonResponse;

final class GetArchivedAuditStatsController
{
    public function __construct(
        private readonly GetArchivedAuditStats $useCase,
    ) {}

    public function __invoke(GetArchivedAuditStatsRequest $request): JsonResponse
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
