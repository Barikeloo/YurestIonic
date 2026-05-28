<?php

declare(strict_types=1);

namespace App\AuditSavedView\Infrastructure\Entrypoint\Http;

use App\AuditSavedView\Application\UpdateAuditSavedView\UpdateAuditSavedView;
use App\AuditSavedView\Domain\Exception\AuditSavedViewNotFoundException;
use App\AuditSavedView\Infrastructure\Entrypoint\Http\Requests\UpdateAuditSavedViewRequest;
use Illuminate\Http\JsonResponse;

final class UpdateAuditSavedViewController
{
    public function __construct(
        private readonly UpdateAuditSavedView $useCase,
    ) {}

    public function __invoke(UpdateAuditSavedViewRequest $request): JsonResponse
    {
        try {
            $response = ($this->useCase)($request->toCommand());
        } catch (AuditSavedViewNotFoundException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 404);
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse($response->toArray(), 200);
    }
}
