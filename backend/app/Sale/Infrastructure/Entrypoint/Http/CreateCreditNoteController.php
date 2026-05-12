<?php

declare(strict_types=1);

namespace App\Sale\Infrastructure\Entrypoint\Http;

use App\Sale\Application\CreateCreditNote\CreateCreditNote;
use App\Sale\Domain\Exception\ParentSaleNotFoundException;
use App\Sale\Infrastructure\Entrypoint\Http\Requests\CreateCreditNoteRequest;
use App\Shared\Infrastructure\Tenant\TenantContext;
use Illuminate\Http\JsonResponse;

final class CreateCreditNoteController
{
    public function __construct(
        private readonly CreateCreditNote $createCreditNote,
        private readonly TenantContext $tenantContext,
    ) {}

    public function __invoke(CreateCreditNoteRequest $request): JsonResponse
    {
        try {
            $response = ($this->createCreditNote)(
                $request->toCommand($this->tenantContext->restaurantUuid())
            );
        } catch (ParentSaleNotFoundException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 404);
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse($response->toArray(), 201);
    }
}
