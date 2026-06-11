<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure\Entrypoint\Http;

use App\Reporting\Application\SendScheduledReportNow\SendScheduledReportNow;
use App\Reporting\Application\SendScheduledReportNow\SendScheduledReportNowCommand;
use App\Reporting\Domain\Exception\ScheduledReportNotFoundException;
use App\Shared\Infrastructure\Tenant\TenantContext;
use Illuminate\Http\JsonResponse;

final readonly class SendScheduledReportNowController
{
    public function __construct(
        private SendScheduledReportNow $useCase,
    ) {}

    public function __invoke(string $uuid): JsonResponse
    {
        $restaurantId = app(TenantContext::class)->restaurantId();

        if ($restaurantId === null) {
            return new JsonResponse(['error' => 'No restaurant context'], 403);
        }

        try {
            $response = ($this->useCase)(new SendScheduledReportNowCommand(
                restaurantId: $restaurantId,
                uuid:         $uuid,
            ));
        } catch (ScheduledReportNotFoundException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 404);
        } catch (\Throwable $e) {
            report($e);
            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse($response->toArray(), 200);
    }
}
