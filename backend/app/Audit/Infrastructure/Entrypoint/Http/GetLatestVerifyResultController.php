<?php

declare(strict_types=1);

namespace App\Audit\Infrastructure\Entrypoint\Http;

use App\Audit\Application\GetLatestVerifyResult\GetLatestVerifyResult;
use App\Audit\Application\GetLatestVerifyResult\GetLatestVerifyResultCommand;
use App\Shared\Infrastructure\Tenant\TenantContext;
use Illuminate\Http\JsonResponse;

final class GetLatestVerifyResultController
{
    public function __construct(
        private readonly GetLatestVerifyResult $useCase,
    ) {}

    public function __invoke(): JsonResponse
    {
        try {

            $tenantContext = app(TenantContext::class);

            $command = new GetLatestVerifyResultCommand(
                restaurantId: (string) $tenantContext->restaurantUuid(),
            );

            $response = ($this->useCase)($command);
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse($response->toArray(), 200);
    }
}
