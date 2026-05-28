<?php

declare(strict_types=1);

namespace App\AuditSavedView\Infrastructure\Entrypoint\Http;

use App\AuditSavedView\Application\CreateAuditSavedView\CreateAuditSavedView;
use App\AuditSavedView\Infrastructure\Entrypoint\Http\Requests\CreateAuditSavedViewRequest;
use Illuminate\Http\JsonResponse;

final class CreateAuditSavedViewController
{
    public function __construct(
        private readonly CreateAuditSavedView $useCase,
    ) {}

    public function __invoke(CreateAuditSavedViewRequest $request): JsonResponse
    {
        try {
            $response = ($this->useCase)($request->toCommand());
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse($response->toArray(), 201);
    }
}
