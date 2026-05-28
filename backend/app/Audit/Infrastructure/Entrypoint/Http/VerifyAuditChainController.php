<?php

declare(strict_types=1);

namespace App\Audit\Infrastructure\Entrypoint\Http;

use App\Audit\Application\VerifyAuditChain\VerifyAuditChain;
use App\Audit\Infrastructure\Entrypoint\Http\Requests\VerifyAuditChainRequest;
use Illuminate\Http\JsonResponse;

final class VerifyAuditChainController
{
    public function __construct(
        private readonly VerifyAuditChain $useCase,
    ) {}

    public function __invoke(VerifyAuditChainRequest $request): JsonResponse
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
