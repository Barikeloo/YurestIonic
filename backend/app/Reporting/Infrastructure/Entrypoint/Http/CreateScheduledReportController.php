<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure\Entrypoint\Http;

use App\Reporting\Application\CreateScheduledReport\CreateScheduledReport;
use App\Reporting\Domain\Exception\InvalidScheduleException;
use App\Reporting\Infrastructure\Entrypoint\Http\Requests\CreateScheduledReportRequest;
use Illuminate\Http\JsonResponse;

final readonly class CreateScheduledReportController
{
    public function __construct(
        private CreateScheduledReport $useCase,
    ) {}

    public function __invoke(CreateScheduledReportRequest $request): JsonResponse
    {
        try {
            $response = ($this->useCase)($request->toCommand());
        } catch (InvalidScheduleException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            report($e);
            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse($response->toArray(), 201);
    }
}
