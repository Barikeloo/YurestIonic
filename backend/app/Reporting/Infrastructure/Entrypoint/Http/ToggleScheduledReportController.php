<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure\Entrypoint\Http;

use App\Reporting\Application\ToggleScheduledReport\ToggleScheduledReport;
use App\Reporting\Application\ToggleScheduledReport\ToggleScheduledReportCommand;
use App\Reporting\Domain\Exception\ScheduledReportNotFoundException;
use App\Shared\Infrastructure\Tenant\TenantContext;
use Illuminate\Http\JsonResponse;

final readonly class ToggleScheduledReportController
{
    public function __construct(
        private ToggleScheduledReport $useCase,
    ) {}

    public function __invoke(string $uuid): JsonResponse
    {
        $restaurantId = app(TenantContext::class)->restaurantId();

        if ($restaurantId === null) {
            return new JsonResponse(['error' => 'No restaurant context'], 403);
        }

        try {
            $result = ($this->useCase)(new ToggleScheduledReportCommand(
                restaurantId: $restaurantId,
                uuid:         $uuid,
            ));
        } catch (ScheduledReportNotFoundException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 404);
        } catch (\Throwable $e) {
            report($e);
            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse($result, 200);
    }
}
