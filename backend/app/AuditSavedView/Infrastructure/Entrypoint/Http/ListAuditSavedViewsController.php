<?php

declare(strict_types=1);

namespace App\AuditSavedView\Infrastructure\Entrypoint\Http;

use App\AuditSavedView\Application\ListAuditSavedViews\ListAuditSavedViews;
use App\AuditSavedView\Infrastructure\Entrypoint\Http\Requests\ListAuditSavedViewsRequest;
use Illuminate\Http\JsonResponse;

final class ListAuditSavedViewsController
{
    public function __construct(
        private readonly ListAuditSavedViews $useCase,
    ) {}

    public function __invoke(ListAuditSavedViewsRequest $request): JsonResponse
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
