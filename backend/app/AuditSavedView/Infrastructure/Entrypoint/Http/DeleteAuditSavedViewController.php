<?php

declare(strict_types=1);

namespace App\AuditSavedView\Infrastructure\Entrypoint\Http;

use App\AuditSavedView\Application\DeleteAuditSavedView\DeleteAuditSavedView;
use App\AuditSavedView\Domain\Exception\AuditSavedViewNotFoundException;
use App\AuditSavedView\Infrastructure\Entrypoint\Http\Requests\DeleteAuditSavedViewRequest;
use Illuminate\Http\JsonResponse;

final class DeleteAuditSavedViewController
{
    public function __construct(
        private readonly DeleteAuditSavedView $useCase,
    ) {}

    public function __invoke(DeleteAuditSavedViewRequest $request): JsonResponse
    {
        try {
            ($this->useCase)($request->toCommand());
        } catch (AuditSavedViewNotFoundException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 404);
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse(null, 204);
    }
}
