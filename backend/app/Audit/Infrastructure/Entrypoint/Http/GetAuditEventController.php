<?php

declare(strict_types=1);

namespace App\Audit\Infrastructure\Entrypoint\Http;

use App\Audit\Application\GetAuditEvent\GetAuditEvent;
use App\Audit\Domain\Exception\AuditLogNotFoundException;
use App\Audit\Infrastructure\Entrypoint\Http\Requests\GetAuditEventRequest;
use Illuminate\Http\JsonResponse;

final class GetAuditEventController
{
    public function __construct(
        private readonly GetAuditEvent $useCase,
    ) {}

    public function __invoke(GetAuditEventRequest $request): JsonResponse
    {
        try {
            $response = ($this->useCase)($request->toCommand());
        } catch (AuditLogNotFoundException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 404);
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse($response->toArray(), 200);
    }
}
