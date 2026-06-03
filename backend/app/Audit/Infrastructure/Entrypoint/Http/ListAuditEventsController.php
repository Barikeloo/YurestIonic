<?php

declare(strict_types=1);

namespace App\Audit\Infrastructure\Entrypoint\Http;

use App\Audit\Application\ListAuditEvents\ListAuditEvents;
use App\Audit\Domain\Exception\ForbiddenAuditAccessException;
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
            $command = $request->toCommand();

            if ($command->includeArchived) {
                $authUserUuid = $request->session()->get('auth_user_id');
                if (! is_string($authUserUuid) || $authUserUuid === '') {
                    throw ForbiddenAuditAccessException::includeArchivedNotAllowed();
                }
            }

            $response = ($this->useCase)($command);
        } catch (ForbiddenAuditAccessException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 403);
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse($response->toArray(), 200);
    }
}
