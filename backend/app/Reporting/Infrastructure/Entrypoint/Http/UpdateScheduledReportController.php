<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure\Entrypoint\Http;

use App\Reporting\Application\UpdateScheduledReport\UpdateScheduledReport;
use App\Reporting\Domain\Exception\InvalidScheduleException;
use App\Reporting\Domain\Exception\ScheduledReportNotFoundException;
use App\Reporting\Infrastructure\Entrypoint\Http\Requests\UpdateScheduledReportRequest;
use Illuminate\Http\JsonResponse;

final readonly class UpdateScheduledReportController
{
    public function __construct(
        private UpdateScheduledReport $useCase,
    ) {}

    public function __invoke(UpdateScheduledReportRequest $request): JsonResponse
    {
        try {
            ($this->useCase)($request->toCommand());
        } catch (ScheduledReportNotFoundException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 404);
        } catch (InvalidScheduleException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            report($e);
            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse(null, 204);
    }
}
