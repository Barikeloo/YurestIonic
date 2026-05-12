<?php

declare(strict_types=1);

namespace App\Cash\Infrastructure\Entrypoint\Http;

use App\Cash\Application\GetZReport\GetZReport;
use App\Cash\Domain\Exception\ZReportNotFoundException;
use App\Cash\Infrastructure\Entrypoint\Http\Requests\GetZReportRequest;
use Illuminate\Http\JsonResponse;

final class GetZReportController
{
    public function __construct(
        private readonly GetZReport $getZReport,
    ) {}

    public function __invoke(GetZReportRequest $request): JsonResponse
    {
        try {
            $response = ($this->getZReport)($request->toCommand());
        } catch (ZReportNotFoundException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 404);
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse($response->toArray(), 200);
    }
}
